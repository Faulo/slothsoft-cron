def runPhp(String arguments) {
    if (isUnix()) {
        exec "php ${arguments}"
    } else {
        bat """@set "PATH=C:\\cron-php-zts;%PATH%"
@set "PHPRC=C:\\cron-php-zts"
@set "PHP_INI_SCAN_DIR=C:\\cron-php-zts\\conf.d"
@php ${arguments}"""
    }
}

def runNativeChecks(String platform, String version) {
    def composer = platform == 'linux' ? '/usr/local/bin/composer' : 'C:/cron-php-zts/composer.phar'
    runPhp '--ri parallel'

    for (String variant in ['lowest', 'stable']) {
        catchError(stageResult: 'UNSTABLE', buildResult: 'UNSTABLE', catchInterruptions: false) {
            runPhp "${composer} update --no-interaction --no-progress --prefer-${variant}"
            runPhp "vendor/bin/phpunit --log-junit .reports/${version}-${variant}.xml"
            if (platform == 'linux' && version == '8.2' && variant == 'stable') {
                execStatus(
                    script: 'php vendor/bin/phpstan analyse --no-progress --configuration=phpstan.neon.dist --error-format=junit',
                    stdout: '.reports/phpstan.xml'
                )
            }
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
        stage('PHP ZTS with ext-parallel') {
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
                    stage('Native checks') {
                        steps {
                            checkout scm
                            fileOperations([folderDeleteOperation('.reports'), folderCreateOperation('.reports')])
                            script {
                                def platform = env.PLATFORM
                                def version = env.PHP_SERIES
                                def recipe = platform == 'linux'
                                    ? '-f .ddev/web-build/prepend.Dockerfile.php-zts .ddev/web-build'
                                    : '-f .jenkins/Dockerfile.windows-zts .jenkins'
                                docker.build("slothsoft-cron-zts:${version}", "--build-arg CRON_PHP_VERSION=${version} ${recipe}").inside {
                                    runNativeChecks(platform, version)
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
    }
}
