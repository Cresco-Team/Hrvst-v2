import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import prettier from 'eslint-config-prettier'
import importPlugin from 'eslint-plugin-import'
import vue from 'eslint-plugin-vue'

export default defineConfigWithVueTs(
	vue.configs['flat/recommended'],
	vueTsConfigs.recommended,
	{
		ignores: [
			'vendor',
			'node_modules',
			'public',
			'bootstrap/ssr',
			'tailwind.config.js',
			'vite.config.ts',
			'resources/js/components/ui/**/*',
		],
	},
	{
		plugins: {
			import: importPlugin,
		},
		settings: {
			'import/resolver': {
				typescript: {
					alwaysTryTypes: true,
					project: './tsconfig.json',
				},
			},
		},
		rules: {
			'vue/multi-word-component-names': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/consistent-type-imports': [
				'error',
				{
					prefer: 'type-imports',
					fixStyle: 'separate-type-imports',
				},
			],
			'import/order': [
				'error',
				{
					groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
					alphabetize: {
						order: 'asc',
						caseInsensitive: true,
					},
				},
			],
		},
	},
	prettier,
	{
		files: ['resources/js/**/*.vue'],
		rules: {
			'vue/html-indent': ['error', 4],
			'vue/max-attributes-per-line': [
				'error',
				{
					singleline: { max: 1 },
					multiline: { max: 1 },
				},
			],
			'vue/html-closing-bracket-newline': [
				'error',
				{
					singleline: 'never',
					multiline: 'always',
				},
			],
			'vue/first-attribute-linebreak': [
				'error',
				{
					singleline: 'beside',
					multiline: 'below',
				},
			],
		}
	}
)
