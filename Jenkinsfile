pipeline {
  options {
    skipDefaultCheckout()
  }
  agent {
    kubernetes {
      cloud 'gke'
      yaml """
apiVersion: v1
kind: Pod
metadata:
  name: example-pod
spec:
  serviceAccountName: jenkins
  containers:
  - name: kubectl
    image: bitnami/kubectl:1.27.4
    command:
    - sleep
    - "3600"
    tty: true
    securityContext:
      runAsUser: 1000
      runAsGroup: 1000
  - name: docker
    image: docker:20.10-dind
    securityContext:
      privileged: true
    command:
      - dockerd-entrypoint.sh
      - --host=unix:///var/run/docker.sock
    tty: true
  - name: mysql-client
    image: mysql:8.0
    command:
      - sleep
      - "3600"
    tty: true
"""
    }
  }
  parameters {
    choice(name: 'choices', choices: ['deploy-blue', 'deploy-green', 'switch-traffic', 'rollout-blue','delete-all'], description: 'pick one')
    choice(name: 'tag', choices: ['blue', 'green'], description: 'pick one')
  }

  environment {
    IMAGE_NAME = "iamsicher/nameapp"
    KUBE_NAMESPACE = "name-app"
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    stage('Deploy MySQL') {
      steps {
        container('mysql-client') {
          sh "kubectl -n ${KUBE_NAMESPACE} apply -f mysql.yaml"
        }
      }
    }

    stage('Init MySQL') {
      steps {
        container('mysql-client') {
          withCredentials([usernamePassword(credentialsId: 'mysql-credentials-id', usernameVariable: 'MYSQL_USER', passwordVariable: 'MYSQL_PASS')]) {
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS < init-db.sql
            '''
          }
        }
      }
    }

    stage('Build') {
      steps {
        script {
          def buildfile = ""
          if (params.choices == 'deploy-blue') {
            buildfile = 'blue-Dockerfile'
          } else if (params.choices == 'deploy-green') {
            buildfile = 'green-Dockerfile'
          } else {
            echo "Skipping Build Stage"
          }

          if (buildfile) {
            container('docker') {
              withCredentials([usernamePassword(credentialsId: 'dockerhub-credentials-id', usernameVariable: 'DOCKERHUB_USER', passwordVariable: 'DOCKERHUB_PASS')]) {
                sh '''
                  echo $DOCKERHUB_PASS | docker login -u $DOCKERHUB_USER --password-stdin
                '''
                sh "docker build -t ${IMAGE_NAME}:${params.tag} -f ${buildfile} ."
                sh "docker push ${IMAGE_NAME}:${params.tag}"
                echo "Pushed image done"
              }
            }
          }
        }
      }
    }

    stage('Migration MySQL') {
      steps {
        container('mysql-client') {
          withCredentials([usernamePassword(credentialsId: 'mysql-credentials-id', usernameVariable: 'MYSQL_USER', passwordVariable: 'MYSQL_PASS')]) {
            script {
              if (params.choices == 'deploy-green') {
                sh '''
                  mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS < migration-db.sql
                '''
                echo "DEPLOY DONE"
              } else {
                echo "Skipping Migration MySQL"
              }
            }
          }
        }
      }
    }

    stage('Deploy') {
      steps {
        container('kubectl') {
          script {
            def deployfile = ""
            if (params.choices == 'deploy-blue') {
              deployfile = 'blue-deployment.yaml'
            } else if (params.choices == 'deploy-green') {
              deployfile = 'green-deployment.yaml'
            } else {
              echo "Skipping Deploy Stage"
            }
            if (deployfile) {
              sh "kubectl apply -f ${deployfile} -n ${KUBE_NAMESPACE}"
              echo "DEPLOY DONE"
            }
          }
        }
      }
    }

    stage('Query MySQL') {
      steps {
        container('mysql-client') {
          withCredentials([usernamePassword(credentialsId: 'mysql-credentials-id', usernameVariable: 'MYSQL_USER', passwordVariable: 'MYSQL_PASS')]) {
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS -D namedb -e "SELECT * FROM names;"
            '''
          }
        }
      }
    }
  }
}
