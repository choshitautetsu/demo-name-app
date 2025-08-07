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
    choice(name: 'choices', choices: ['deploy-mysql', 'deploy-blue', 'deploy-green', 'switch-traffic', 'rollout-blue', 'delete-all'], description: 'pick one')
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
      when {
        expression { params.choices == 'deploy-mysql' }
      }
      steps {
        container('kubectl') {
          sh "kubectl -n ${KUBE_NAMESPACE} apply -f mysql.yaml"
          // 等待 MySQL Pod Ready
          sh "kubectl -n ${KUBE_NAMESPACE} wait --for=condition=ready pod -l app=mysql --timeout=120s"
          echo "DEPLOY MYSQL DONE AND POD READY!!!"
        }
      }
    }

    stage('Init MySQL') {
      when {
        expression { params.choices == 'deploy-blue' }
      }
      steps {
        container('mysql-client') {
          withCredentials([usernamePassword(credentialsId: 'mysql-credentials-id', usernameVariable: 'MYSQL_USER', passwordVariable: 'MYSQL_PASS')]) {
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS < init-db.sql
            '''
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS -D namedb -e "SELECT * FROM names;"
            '''
            echo "INIT MYSQL DONE!!!"
          }
        }
      }
    }

    stage('Build') {
      when {
        expression { params.choices == 'deploy-blue' || params.choices == 'deploy-green' }
      }
      steps {
        script {
          def buildfile = (params.choices == 'deploy-blue') ? 'blue-Dockerfile' : 'green-Dockerfile'
          container('docker') {
            withCredentials([usernamePassword(credentialsId: 'dockerhub-credentials-id', usernameVariable: 'DOCKERHUB_USER', passwordVariable: 'DOCKERHUB_PASS')]) {
              sh '''
                echo $DOCKERHUB_PASS | docker login -u $DOCKERHUB_USER --password-stdin
              '''
              sh "docker build -t ${IMAGE_NAME}:${params.tag} -f ${buildfile} ."
              sh "docker push ${IMAGE_NAME}:${params.tag}"
              echo "PUSHED IMAGE DONE!!!"
            }
          }
        }
      }
    }

    stage('Migration MySQL') {
      when {
        expression { params.choices == 'deploy-green' }
      }
      steps {
        container('mysql-client') {
          withCredentials([usernamePassword(credentialsId: 'mysql-credentials-id', usernameVariable: 'MYSQL_USER', passwordVariable: 'MYSQL_PASS')]) {
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS < migration-db.sql
            '''
            sh '''
              mysql -h mysql.${KUBE_NAMESPACE}.svc.cluster.local -u$MYSQL_USER -p$MYSQL_PASS -D namedb -e "SELECT * FROM names;"
            '''
            echo "MIGRATION MYSQL DONE!!!"
          }
        }
      }
    }

    stage('Deploy') {
      when {
        expression { params.choices == 'deploy-blue' || params.choices == 'deploy-green' }
      }
      steps {
        container('kubectl') {
          script {
            def deployfile = (params.choices == 'deploy-blue') ? 'blue-deployment.yaml' : 'green-deployment.yaml'
            sh "kubectl apply -f ${deployfile} -n ${KUBE_NAMESPACE}"
            echo "DEPLOY STAGE DONE!!!"
          }
        }
      }
    }

    stage('Switch Traffic') {
      when {
        expression { params.choices == 'switch-traffic' }
      }
      steps {
        container('kubectl') {
          sh "kubectl -n ${KUBE_NAMESPACE} patch svc blue-name-app-svc -p '{\"spec\":{\"selector\":{\"app\":\"green-name-app\"}}}'"
          sh "kubectl -n ${KUBE_NAMESPACE} get ep"
          echo "SWITCH TRAFFIC DONE!!!"
        }
      }
    }

    stage('Rollout Blue') {
      when {
        expression { params.choices == 'rollout-blue' }
      }
      steps {
        container('kubectl') {
          sh "kubectl -n ${KUBE_NAMESPACE} patch svc blue-name-app-svc -p '{\"spec\":{\"selector\":{\"app\":\"blue-name-app\"}}}'"
          sh "kubectl -n ${KUBE_NAMESPACE} get ep"
          echo "ROLLOUT BLUE DONE!!!"
        }
      }
    }

    stage('delete all') {
      steps {
        container('kubectl') {
          script {
            if (params.choices == 'delete-all') {
              sh "kubectl -n ${KUBE_NAMESPACE} delete -f ./"
              echo "DELETE COMPLETE!!!"
            } else {
              echo "SKIPPING DELETE STAGE!!!"
            }
          }
        }
      }
    }

    /*
    stage('Query MySQL') {
      when {
        expression { /*    * / }
      }
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
    */
  }
}
