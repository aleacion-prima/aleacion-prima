import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, readFileSync, renameSync } from 'node:fs';
import { basename, join, resolve } from 'node:path';

const raiz = resolve(import.meta.dirname, '..');
const dirDiagramas = join(raiz, 'docs', 'diagramas');
const dirImg = join(dirDiagramas, 'img');
const carpetas = ['', 'casos-de-uso'];
const imagen = 'plantuml/plantuml';

function correr(comando: string, args: string[]): void {
  const res = spawnSync(comando, args, { stdio: 'inherit' });
  if (res.status !== 0) {
    console.error(`\n✖ Falló: ${comando} ${args.join(' ')}`);
    process.exit(res.status ?? 1);
  }
}

function archivosPuml(): string[] {
  return carpetas.flatMap((carpeta) => {
    const dir = join(dirDiagramas, carpeta);
    return readdirSync(dir)
      .filter((nombre) => nombre.endsWith('.puml'))
      .map((nombre) => join(dir, nombre));
  });
}

function normalizarNombres(): void {
  for (const ruta of archivosPuml()) {
    const esperado = basename(ruta, '.puml');
    const primeraLinea = readFileSync(ruta, 'utf8').split('\n')[0] ?? '';
    const nombreInterno = /^@startuml\s+(\S+)/.exec(primeraLinea.trim())?.[1];
    if (!nombreInterno || nombreInterno === esperado) continue;

    const origen = join(dirImg, `${nombreInterno}.svg`);
    const destino = join(dirImg, `${esperado}.svg`);
    if (existsSync(origen)) {
      renameSync(origen, destino);
      console.log(`  ${nombreInterno}.svg → ${esperado}.svg`);
    }
  }
}

mkdirSync(dirImg, { recursive: true });

const usuario =
  process.getuid && process.getgid ? ['-u', `${process.getuid()}:${process.getgid()}`] : [];

console.log('Exportando diagramas a SVG…');
correr('docker', [
  'run',
  '--rm',
  ...usuario,
  '-v',
  `${dirDiagramas}:/data`,
  imagen,
  '-tsvg',
  '-o',
  '/data/img',
  ...carpetas.map((carpeta) => (carpeta ? `/data/${carpeta}` : '/data')),
]);

normalizarNombres();

if (process.argv.includes('--borrar-imagen')) {
  console.log('Borrando la imagen de PlantUML…');
  correr('docker', ['rmi', imagen]);
}

console.log(`\n✔ SVG en ${dirImg}`);
for (const nombre of readdirSync(dirImg)
  .filter((n) => n.endsWith('.svg'))
  .sort()) {
  console.log(`  ${nombre}`);
}
