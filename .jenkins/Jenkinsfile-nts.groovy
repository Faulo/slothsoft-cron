def runFallbackChecks(String version) {
    for (String variant in ['lowest', 'stable']) {
        catchError(stageResult: 'UNSTABLE', buildResult: 'UNSTABLE', catchInterruptions: false) {
            exec "composer update --no-interaction --no-progress --prefer-${variant}"
            exec "php vendor/bin/phpunit --log-junit .reports/${version}-${variant}.xml"
        }
    }
}

pipeline {
    agent none
    options {
        disableConcurrentBuilds()
        disableResume()
        disableRestartFromStage()
        skipDefaultCheckout()
    }
    environment {
        COMPOSER_ROOT_VERSION = 'dev-main'
        COMPOSER_PROCESS_TIMEOUT = '3600'
        COMPOSER_ALLOW_SUPERUSER = '1'
    }
    stages {
        stage('PHP NTS') {
            matrix {
                axes {
                    axis {
                        name 'PLATFORM'
                        values 'linux', 'windows'
                    }
                    axis {
                        name 'PHP_SERIES'
                        values '8.2', '8.3', '8.4', '8.5'
                    }
                }
                agent {
                    label "${PLATFORM} && docker"
                }
                stages {
                    stage('Serial fallback') {
                        steps {
                            checkout scm
                            fileOperations([folderDeleteOperation('.reports'), folderCreateOperation('.reports')])
                            script {
                                docker.image("faulo/farah:${env.PHP_SERIES}").inside {
                                    exec 'php -v'
                                    exec "php -r \"exit(PHP_ZTS || extension_loaded('parallel') ? 1 : 0);\""
                                    runFallbackChecks(env.PHP_SERIES)
                                }
                            }
                        }
                        post {
                            always {
                                junit(testResults: '.reports/*.xml', allowEmptyResults: true)
                            }
                        }
                    }
                }
            }
        }
        stage('PHP 8.2 ZTS without ext-parallel') {
            agent {
                label 'linux && docker'
            }
            steps {
                checkout scm
                fileOperations([folderDeleteOperation('.reports'), folderCreateOperation('.reports')])
                script {
                    docker.build(
                        'slothsoft-cron-zts-without-parallel:8.2',
                        '--target cron-php-zts-base -f .ddev/web-build/prepend.Dockerfile.php-zts .ddev/web-build'
                    ).inside {
                        exec 'php -v'
                        exec "php -r \"exit(PHP_ZTS && !extension_loaded('parallel') ? 0 : 1);\""
                        runFallbackChecks('8.2-zts')
                    }
                }
            }
            post {
                always {
                    junit(testResults: '.reports/*.xml', allowEmptyResults: true)
                }
            }
        }
    }
}
