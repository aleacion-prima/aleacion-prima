import { execFileSync } from 'node:child_process';
import { join, relative } from 'node:path';

const RAIZ = join(import.meta.dirname, '..');
const API = join(RAIZ, 'apps/api');

const archivos = process.argv.slice(2).map((ruta) => relative(API, ruta));

if (archivos.length > 0) {
  execFileSync(
    'docker',
    ['compose', 'run', '--rm', '--no-deps', '-T', 'api', 'vendor/bin/pint', ...archivos],
    { cwd: RAIZ, stdio: 'inherit' },
  );
}
