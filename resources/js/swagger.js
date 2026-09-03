import SwaggerUI from 'swagger-ui-dist/swagger-ui-es-bundle.js';

import 'swagger-ui-dist/swagger-ui.css';

SwaggerUI({
	url: '/openapi.yaml',
	dom_id: '#swagger-ui',
	deepLinking: true,
	docExpansion: 'list',
	defaultModelExpandDepth: 2,
	defaultModelsExpandDepth: 1,
	defaultModelRendering: 'example',
	displayRequestDuration: true,
	displayOperationId: false,
	filter: true,
	operationsSorter: 'method',
	persistAuthorization: false,
	requestSnippetsEnabled: true,
	showCommonExtensions: true,
	showExtensions: true,
	tagsSorter: 'alpha',
	tryItOutEnabled: true,
	validatorUrl: null,
	onComplete: () => document.body.classList.add('swagger-ready'),
});