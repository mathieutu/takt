import antfu from '@antfu/eslint-config'
import tailwind from 'eslint-plugin-better-tailwindcss'

export default antfu(
  {
    formatters: true,
    vue: true,
    typescript: true,
    ignores: [
      '**/vendor/**',
      '**/storage/**',
      'resources/js/wayfinder/**',
      'public/**',
      '.ai/guidelines/**',
      '.claude/**',
      '.github/**',
      'docs/**',
      'AGENTS.md',
      'CLAUDE.md',
      '.mcp.json',
      'boost.json',

    ],
  },
  {
    ...tailwind.configs.recommended,
    settings: {
      [tailwind.meta.name]: {
        entryPoint: 'resources/css/app.css',
      },
    },
    rules: {
      // 'better-tailwindcss/no-unknown-classes': ['error', {ignore: ['^pi$', '^pi-.+']}],
    },
  },
  {
    rules: {
      'antfu/top-level-function': 'off',
      'antfu/if-newline': 'off',
      'ts/consistent-type-definitions': ['error', 'type'],
      // 'style/max-len': ['error', { code: 120, ignoreTrailingComments: true }],
      'import/consistent-type-specifier-style': ['error', 'prefer-top-level'], // @see https://github.com/9romise/eslint-plugin-import-lite/issues/22
      'style/operator-linebreak': ['error', 'before', { overrides: { '=': 'after' } }],
      'style/no-extra-parens': 'off',
      'style/brace-style': ['error', '1tbs'],
      'style/multiline-ternary': 'off',
      'style/arrow-parens': ['error', 'as-needed'],
      'prefer-arrow-callback': 'error',
      'func-style': ['error'],
      'style/type-generic-spacing': 'off', // Does not allow comment in generics.
      'style/member-delimiter-style': [
        'error',
        {
          multiline: { delimiter: 'comma', requireLast: true },
          singleline: { delimiter: 'comma', requireLast: false },
          multilineDetection: 'brackets',
        },
      ],
    },
  },
  {
    files: ['**/*.vue'],
    rules: {
      'vue/prop-name-casing': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': [
        'error',
        {
          html: {
            void: 'always',
            normal: 'always',
            component: 'always',
          },
          svg: 'always',
          math: 'always',
        },
      ],
      'vue/max-len': [
        'error',
        {
          code: 120,
          ignoreComments: true,
          ignoreTemplateLiterals: true,
          ignoreStrings: true,
          ignoreHTMLAttributeValues: true,
        },
      ],
      'vue/attribute-hyphenation': ['error', 'never'],
      'vue/v-on-event-hyphenation': ['error', 'never'],
    },
  },
  {
    files: ['**/*.ts', '**/*.tsx'],
    rules: {
      'ts/consistent-type-imports': [
        'error',
        { prefer: 'type-imports', fixStyle: 'inline-type-imports' },
      ],
    },
  },
)
