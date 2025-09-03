<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'API BOB FLOW',
                'description' => 'Documenta��o da API',
                'version' => '1.0.0', // Adicionei a vers�o, � uma boa pr�tica
            ],

            'routes' => [
                /*
                 * Rota para acessar a interface de documenta��o da API
                 */
                'api' => 'api/documentation',
            ],

            'paths' => [
                /*
                 * Defina como true para usar o caminho absoluto nos assets
                 */
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),

                /*
                 * Caminho onde os assets do Swagger UI ser�o armazenados
                 */
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
                /*
                 * Arquivo de documenta��o com a men��o dos esquemas necessarios na nossa aplica��o
                 */
                'docs' => base_path('app/Documentation'),
                /*
                 * Nome do arquivo JSON que ser� gerado com a documenta��o
                 */
                'docs_json' => 'api-docs.json',

                /*
                 * Nome do arquivo YAML (se voc� preferir gerar esse formato)
                 */
                'docs_yaml' => 'api-docs.yaml',

                /*
                 * Formato da documenta��o que ser� usado no Swagger UI (json ou yaml)
                 */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Caminho absoluto para o diret�rio onde as anota��es do Swagger est�o armazenadas
                 */
                'annotations' => [
                    base_path('app'),
                    base_path('app/Http/Resources'), // Adicione esta linha
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            /*
             * Rota para acessar as anota��es do Swagger
             */
            'docs' => 'docs',

            /*
             * Rota para o callback de autentica��o OAuth2
             */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware para controlar o acesso � documenta��o da API
             */
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            /*
             * Op��es para os grupos de rotas
             */
            'group_options' => [],
        ],

        'paths' => [
            /*
             * Caminho absoluto para onde as anota��es parseadas ser�o armazenadas
             */
            'docs' => storage_path('api-docs'),

            /*
             * Caminho absoluto para onde as views da documenta��o ser�o exportadas
             */
            'views' => base_path('resources/views/vendor/l5-swagger'),

            /*
             * Caminho base da API (geralmente se voc� usa algo como /v1, /api)
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * Diret�rios que devem ser exclu�dos da varredura de anota��es
             */
            'excludes' => [],
        ],

        'scanOptions' => [
            /**
             * Configura��o para os processadores padr�o. Permite passar a configura��o para o swagger-php.
             *
             * @link https://zircote.github.io/swagger-php/reference/processors.html
             */
            'default_processors_configuration' => [
                // Exemplo:
                // 'operationId.hash' => true,
                // 'pathFilter' => [
                //    'tags' => [
                //        '/pets/',
                //        '/store/',
                //    ],
                // ],
            ],

            /**
             * Analisador de rotas: usa o padr�o \OpenApi\StaticAnalyser .
             */
            'analyser' => null,

            /**
             * An�lise: usa uma nova inst�ncia de \OpenApi\Analysis .
             */
            'analysis' => null,

            /**
             * Processadores de query customizados para os caminhos.
             * @link https://github.com/zircote/swagger-php/tree/master/Examples/processors/schema-query-parameter
             */
            'processors' => [
                // new \App\SwaggerProcessors\SchemaQueryParameter(),
            ],

            /*
             * Padr�o de arquivo para varredura (default: *.php)
             */
            'pattern' => null,

            /*
             * Caminho absoluto para diret�rios que devem ser exclu�dos da varredura
             */
            'exclude' => [],

            /*
             * Permite gerar especifica��es para OpenAPI 3.0.0 ou 3.1.0.
             * Por padr�o, ser� gerado com a vers�o 3.0.0
             */
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        /*
         * Defini��es de seguran�a da API. Ser�o geradas no arquivo de documenta��o.
         */
        'securityDefinitions' => [
            'securitySchemes' => [
                'sanctum' => [
                    'type' => 'apiKey',
                    'description' => 'Entre com o token no formato (Bearer <token>)',
                    'name' => 'Authorization',
                    'in' => 'header',
                ],
            ],
            'security' => [
                [
                    'sanctum' => [],
                ],
            ],
        ],

        /*
         * Gera a documenta��o sempre que a aplica��o for acessada.
         * Defina como false em produ��o para evitar sobrecarga.
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        /*
         * Gera uma c�pia da documenta��o em formato YAML (se ativado)
         */
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

        /*
         * Configura��o de proxy - necess�rio para balanceadores de carga como o AWS
         */
        'proxy' => false,

        /*
         * Configura��o do plugin para pegar configura��es externas do Swagger UI
         */
        'additional_config_url' => null,

        /*
         * Classifica��o das opera��es na UI. Pode ser 'alpha' (por caminho) ou 'method' (por m�todo HTTP).
         */
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

        /*
         * Passa o par�metro validatorUrl para a inicializa��o do Swagger UI no lado do JS.
         */
        'validator_url' => null,

        /*
         * Configura��es da UI do Swagger
         */
        'ui' => [
            'display' => [
                'dark_mode' => false, // For�ar dark mode
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
            'additional_css' => [
                'vendor/l5-swagger/css/dark-theme.css',
            ],
        ],

        /*
         * Defini��es de constantes que podem ser usadas nas anota��es
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://meu-host.com'),
        ],
    ],
];
