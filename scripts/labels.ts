// scripts/labels.ts
// Uso: node scripts/labels.ts <org>/<repo>
import { execFileSync } from 'node:child_process';

interface Label {
  name: string;
  color: string;
  description: string;
}

const repo = process.argv[2];
if (!repo) {
  console.error('Uso: node scripts/labels.ts <org>/<repo>');
  process.exit(1);
}

const defaults = [
  'bug',
  'documentation',
  'duplicate',
  'enhancement',
  'good first issue',
  'help wanted',
  'invalid',
  'question',
  'wontfix',
];

const labels: Label[] = [
  { name: 'api', color: 'F05340', description: 'Backend en Laravel' },
  { name: 'panel', color: '1976D2', description: 'Panel web (Quasar)' },
  { name: 'movil', color: '26A69A', description: 'App móvil (Capacitor, escaneo QR)' },
  { name: 'web', color: 'BC52EE', description: 'Sitio público (Astro)' },
  { name: 'types', color: '3178C6', description: 'Tipos compartidos de la API' },
  { name: 'config', color: 'C5DEF5', description: 'Configuración compartida, linters y hooks' },
  { name: 'infra', color: '5319E7', description: 'Docker, Caddy, CI/CD y servidor' },
  { name: 'docs', color: '0075CA', description: 'Documentación y diagramas' },
  { name: 'e2e', color: '0E8A16', description: 'Pruebas de extremo a extremo (Playwright)' },
  { name: 'scripts', color: '5C4033', description: 'Scripts del repo' },
  { name: 'bloqueado', color: 'B60205', description: 'No puede avanzar hasta resolver otra cosa' },
  {
    name: 'necesita-info',
    color: 'D876E3',
    description: 'Falta definir algo con el Product Owner',
  },
  { name: 'recortable', color: 'FBCA04', description: 'Fuera del MVP; se recorta primero' },
];

const gh = (args: string[]): void => {
  execFileSync('gh', [...args, '--repo', repo], { stdio: 'inherit' });
};

for (const name of defaults) {
  try {
    gh(['label', 'delete', name, '--yes']);
  } catch {
    // Ya no existía
  }
}

for (const { name, color, description } of labels) {
  gh(['label', 'create', name, '--color', color, '--description', description, '--force']);
}

console.log(`Listo: ${labels.length} labels en ${repo}`);
