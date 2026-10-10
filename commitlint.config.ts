import { RuleConfigSeverity, type UserConfig } from '@commitlint/types';

const config: UserConfig = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'scope-enum': [
      RuleConfigSeverity.Error,
      'always',
      ['api', 'panel', 'web', 'types', 'config', 'infra', 'e2e', 'docs', 'scripts'],
    ],
  },
};

export default config;
