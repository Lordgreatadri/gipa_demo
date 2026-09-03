import SwaggerUI from 'swagger-ui-dist/swagger-ui-es-bundle.js';

import 'swagger-ui-dist/swagger-ui.css';

SwaggerUI({
	url: '/openapi.yaml',
	dom_id: '#swagger-ui',
	deepLinking: true,
	displayRequestDuration: true,
	persistAuthorization: false,
	tryItOutEnabled: true,
});