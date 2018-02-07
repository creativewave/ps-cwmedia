/**
 * How rules are selected?
 * -----------------------------------------------------------------------------
 * Rules outside 'Stylistic rules' group:
 * 1. should not be eslint:recommended already.
 * 2. should not fix obvious errors observable by running code.
 * 3. should not fix obvious errors from careless/unexperienced developers.
 *
 * Rules inside 'Stylistic rules' group:
 * 1. should add real value to the code, and not just be an opinionated TOC.
 * 2. should be a commonly accepted rule by the whole JS OSS community.
 */

module.exports = {
    env: {
        es6: true,
        browser: true,
    },
    extends: ['eslint:recommended'],
    parserOptions: {
        ecmaFeatures: {
            impliedStrict: true,
        },
        ecmaVersion: 8,
        sourceType: 'module',
    },
    plugins: [
        'compat',
    ],
    rules: {
        'array-bracket-newline': ['warn', 'consistent'],
        'array-bracket-spacing': 'warn',
        'arrow-body-style': 'warn',
        'arrow-parens': ['warn', 'as-needed'],
        'arrow-spacing': 'warn',
        'block-scoped-var': 'warn',
        'block-spacing': 'warn',
        'brace-style': 'warn',
        'camelcase': 'warn',
        'class-methods-use-this': 'warn',
        'comma-dangle': ['warn', 'always-multiline'],
        'comma-spacing': 'warn',
        'compat/compat': 'warn',
        'computed-property-spacing': 'warn',
        'dot-notation': 'warn',
        'eol-last': 'warn',
        'func-call-spacing': 'warn',
        'func-names': ['warn', 'as-needed'],
        'function-paren-newline': ['warn', 'consistent'],
        'generator-star-spacing': ['warn', 'after'],
        'indent': ['warn', 4, { SwitchCase: 1 }],
        'key-spacing': 'warn',
        'keyword-spacing': 'warn',
        'linebreak-style': 'warn',
        'lines-between-class-members': 'warn',
        'max-len': ['warn', { code: 120 }],
        'max-params': 'warn',
        'max-statements-per-line': 'warn',
        'new-cap': 'warn',
        'new-parens': 'warn',
        'no-duplicate-imports': 'warn',
        'no-else-return': 'warn',
        'no-lonely-if': 'warn',
        'no-multiple-empty-lines': 'warn',
        'no-trailing-spaces': 'warn',
        'no-unused-vars': 'warn',
        'no-useless-rename': 'warn',
        'object-curly-newline': ['warn', { consistent: true }],
        'object-curly-spacing': ['warn', 'always'],
        'object-shorthand': 'warn',
        'operator-assignment': 'warn',
        'operator-linebreak': 'warn',
        'prefer-arrow-callback': 'warn',
        'prefer-const': 'warn',
        'prefer-destructuring': 'warn',
        'prefer-template': 'warn',
        'quotes': ['warn', 'single'],
        'rest-spread-spacing': 'warn',
        'semi': ['warn', 'never'],
        'space-before-blocks': 'warn',
        'space-before-function-paren': ['warn', { named: 'never'}],
        'space-in-parens': 'warn',
        'space-infix-ops': 'warn',
        'space-unary-ops': 'warn',
        'switch-colon-spacing': 'warn',
        'template-curly-spacing': 'warn',
        'template-tag-spacing': 'warn',
        'yield-star-spacing': 'warn',
        'yoda': ['warn', 'always'],
    },
    settings: {
        polyfills: ['fetch', 'promises'],
    },
}
