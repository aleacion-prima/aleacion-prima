import { spawn } from 'node:child_process';
import { join } from 'node:path';
import { createInterface } from 'node:readline';
import { styleText } from 'node:util';

type Color = Parameters<typeof styleText>[0];

interface Servicio {
  nombre: string;
  color: Color;
  programa: string;
  args: string[];
}

const RAIZ = join(import.meta.dirname, '..');

const SERVICIOS: readonly Servicio[] = [
  {
    nombre: 'api',
    color: 'magenta',
    programa: 'docker',
    args: ['compose', 'up', '--no-attach', 'mongo', '--no-attach', 'postgres'],
  },
  { nombre: 'panel', color: 'cyan', programa: 'pnpm', args: ['--filter', 'panel', 'dev'] },
  { nombre: 'web', color: 'green', programa: 'pnpm', args: ['--filter', 'web', 'dev'] },
];

function elegirServicios(nombres: string[]): Servicio[] {
  if (nombres.length === 0) return [...SERVICIOS];

  const desconocidos = nombres.filter((n) => !SERVICIOS.some((s) => s.nombre === n));
  if (desconocidos.length > 0) {
    const validos = SERVICIOS.map((s) => s.nombre).join(', ');
    console.error(`Servicio desconocido: ${desconocidos.join(', ')}. Opciones: ${validos}`);
    process.exit(1);
  }
  return SERVICIOS.filter((s) => nombres.includes(s.nombre));
}

function iniciar(servicio: Servicio, alTerminar: () => void): void {
  const prefijo = `${styleText(servicio.color, servicio.nombre.padEnd(5))} │ `;

  const proceso = spawn(servicio.programa, servicio.args, {
    cwd: RAIZ,
    env: {
      ...process.env,
      FORCE_COLOR: '1',
      UID: String(process.getuid?.() ?? 1000),
      GID: String(process.getgid?.() ?? 1000),
    },
  });

  for (const salida of [proceso.stdout, proceso.stderr]) {
    createInterface({ input: salida }).on('line', (linea) => {
      console.log(prefijo + linea);
    });
  }

  proceso.on('error', (error) => {
    console.error(`${prefijo}no se pudo iniciar: ${error.message}`);
  });

  proceso.on('exit', (codigo) => {
    console.log(`${prefijo}terminó (código ${codigo ?? 'desconocido'})`);
    alTerminar();
  });
}

const servicios = elegirServicios(process.argv.slice(2));
let activos = servicios.length;

process.on('SIGINT', () => {
  console.log('\nDeteniendo servicios...');
});

for (const servicio of servicios) {
  iniciar(servicio, () => {
    activos -= 1;
    if (activos === 0) process.exit(0);
  });
}
