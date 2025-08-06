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
"""
    }
  }
  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }
    stage('Docker Login') {
      steps {
        container('docker') {
          withCredentials([usernamePassword(credentialsId: 'dockerhub-credentials-id', 
                                            usernameVariable: 'DOCKERHUB_USER', 
                                            passwordVariable: 'DOCKERHUB_PASS')]) {
            sh '''
              echo $DOCKERHUB_PASS | docker login -u $DOCKERHUB_USER --password-stdin
            '''
            sh "docker build -t nameapp:v2 ."
            sh "docker tag nameapp:v2 iamsicher/nameapp:v2"
            sh "docker push iamsicher/nameapp:v2"
            echo "Pushed image done"
          }
        }
      }
    }
    // 你后续的构建、推送镜像等stage
  }
}
