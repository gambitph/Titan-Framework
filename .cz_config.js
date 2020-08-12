'use strict';

// see https://github.com/leonardoanalista/cz-customizable
module.exports = {

  types: [
    { value: 'feat', name: 'feat:     A new feature' },
    { value: 'fix', name: 'fix:      A bug fix' },
    { value: 'theme', name: 'theme:    Design and Theme changes' },
    { value: 'refactor', name: 'refactor: A code change that neither fixes a bug nor adds a feature' },
    { value: 'test', name: 'test:     Adding missing tests' },
    { value: 'chore', name: 'chore:    Changes to the build process or documentation generation' },
    { value: 'wip', name: 'WIP:      Work in progress' }
  ],

  chore: [
    { name: 'composer' },
    { name: 'node' },
    { name: 'git' },
    { name: 'phpstorm' },
    { name: 'cli' },
    { name: 'system' }
  ],

  allowCustomScopes: true,
  allowBreakingChanges: [
    'feat',
    'fix'
  ],

  appendBranchNameToCommitMessage: true,
  breakingPrefix: 'BREAKING: ',
  footerPrefix: 'ISSUES CLOSED: ',

  subjectLimit: 100,
  breaklineChar: '|'

};
