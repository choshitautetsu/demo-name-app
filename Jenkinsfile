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
  securityContext:
    fsGroup: 1000
    runAsUser: 1000
    runAsGroup: 1000
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
    image: docker:24.0.2-dind
    securityContext:
      privileged: true
    tty: true
    command:
    - dockerd-entrypoint.sh
    - --host=unix:///var/run/docker.sock
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
          }
        }
      }
    }
    // 你后续的构建、推送镜像等stage
  }
}
