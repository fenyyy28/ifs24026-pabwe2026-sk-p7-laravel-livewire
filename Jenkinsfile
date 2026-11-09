// Pipeline CI: install -> audit keamanan -> test + gate coverage 100%
// Prasyarat di agent Jenkins: PHP 8.3 (ext: mbstring, xml, zip, gd, pdo_sqlite, pcov), Composer.
pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        APP_ENV     = 'testing'
        COMPOSER_NO_INTERACTION = '1'
        // pcov/xdebug dipakai untuk menghitung coverage
        XDEBUG_MODE = 'coverage'
    }

    stages {
        stage('Checkout') {
            steps { checkout scm }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-progress --prefer-dist'
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Security Audit') {
            steps {
                // exit code != 0 bila ada kerentanan -> stage (dan build) GAGAL
                sh 'composer audit'
            }
        }

        stage('Test & Coverage Gate') {
            steps {
                // gagal bila coverage app/ < 100%
                sh 'php artisan config:clear'
                sh 'php artisan test --coverage --min=100'
            }
        }
    }

    post {
        always { cleanWs() }
        success { echo 'Build OK: audit bersih & coverage 100%.' }
        failure { echo 'Build GAGAL: cek audit / test / coverage.' }
    }
}
