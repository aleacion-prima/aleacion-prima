import { execFileSync } from 'node:child_process';
import { copyFileSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Prepara el entorno de desarrollo de la API: crea lo que falte y se salta lo
 * que ya existe, así se puede correr cuantas veces sea necesario.
 * Uso: pnpm setup
 */

const RAIZ = join(import.meta.dirname, '..');
const RUTA_ENV = join(RAIZ, 'apps/api/.env');
const RUTA_ENV_EJEMPLO = join(RAIZ, 'apps/api/.env.example');

// ---------- Utilidades ----------

function paso(mensaje: string): void {
  console.log(`\n▶ ${mensaje}`);
}

function compose(...args: string[]): void {
  execFileSync('docker', ['compose', ...args], {
    cwd: RAIZ,
    stdio: 'inherit',
    env: {
      ...process.env,
      UID: String(process.getuid?.() ?? 1000),
      GID: String(process.getgid?.() ?? 1000),
    },
  });
}

function artisan(...args: string[]): void {
  compose('run', '--rm', 'api', 'php', 'artisan', ...args);
}

class ArchivoEnv {
  private readonly ruta: string;
  private contenido: string;

  constructor(ruta: string) {
    this.ruta = ruta;
    this.contenido = readFileSync(ruta, 'utf8');
  }

  obtener(nombre: string): string | undefined {
    const valor = this.contenido.match(new RegExp(`^${nombre}=(.*)$`, 'm'))?.[1]?.trim();
    return valor === '' ? undefined : valor;
  }

  definir(nombre: string, valor: string): void {
    const patron = new RegExp(`^${nombre}=.*$`, 'm');
    this.contenido = patron.test(this.contenido)
      ? this.contenido.replace(patron, `${nombre}=${valor}`)
      : `${this.contenido.trimEnd()}\n${nombre}=${valor}\n`;
  }

  guardar(): void {
    writeFileSync(this.ruta, this.contenido);
  }
}

// ---------- Pasos ----------

function verificarDocker(): void {
  paso('Revisando que Docker esté corriendo');
  try {
    execFileSync('docker', ['info'], { stdio: 'ignore' });
  } catch {
    const ayuda =
      process.platform === 'linux' ? 'sudo systemctl start docker' : 'abre Docker Desktop';
    console.error(`Docker no está corriendo. Para iniciarlo: ${ayuda}`);
    process.exit(1);
  }
}

function prepararEnv(): void {
  if (!existsSync(RUTA_ENV)) {
    paso('Creando apps/api/.env desde .env.example');
    copyFileSync(RUTA_ENV_EJEMPLO, RUTA_ENV);
  }

  const env = new ArchivoEnv(RUTA_ENV);
  if (!env.obtener('REVERB_APP_KEY')) {
    paso('Poniendo las llaves fijas de Reverb para desarrollo');
    env.definir('REVERB_APP_ID', 'aleacion');
    env.definir('REVERB_APP_KEY', 'aleacion-local');
    env.definir('REVERB_APP_SECRET', 'aleacion-local-secret');
    env.guardar();
  }
}

function prepararApi(): void {
  paso('Construyendo la imagen de la API');
  compose('build');

  paso('Instalando dependencias de PHP');
  compose('run', '--rm', 'api', 'composer', 'install');

  if (!new ArchivoEnv(RUTA_ENV).obtener('APP_KEY')) {
    paso('Generando APP_KEY');
    artisan('key:generate');
  }
}

function prepararBasesDeDatos(): void {
  paso('Levantando PostgreSQL y MongoDB');
  compose('up', '-d', '--wait', 'postgres', 'mongo');

  const dbHost = new ArchivoEnv(RUTA_ENV).obtener('DB_HOST');
  if (dbHost !== 'postgres') {
    console.warn(
      `\n⚠ DB_HOST=${dbHost ?? '(vacío)'} no es la BD local; se omiten las migraciones.`,
    );
    return;
  }

  paso('Corriendo migraciones');
  artisan('migrate');
}

// ---------- Ejecución ----------

verificarDocker();
prepararEnv();
prepararApi();
prepararBasesDeDatos();

console.log('\n✅ Listo. Para levantar la API: pnpm api:up');
