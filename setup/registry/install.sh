# https://www.paulsblog.dev/how-to-install-a-private-docker-container-registry-in-kubernetes/#google_vignette

export REGISTRY_HOST=docker-registry.127.0.0.1.nip.io
export REGISTRY_USER=admin
export REGISTRY_PASS=registryPass
export DESTINATION_FOLDER=./registry-creds

if [ "$1" == "install" ]; then

  if [ -d "${DESTINATION_FOLDER}" ]; then
    rm -rf ${DESTINATION_FOLDER}
  fi
    
  # Backup credentials to local files (in case you'll forget them later on)
  mkdir -p ${DESTINATION_FOLDER}
  echo ${REGISTRY_USER} >> ${DESTINATION_FOLDER}/registry-user.txt
  echo ${REGISTRY_PASS} >> ${DESTINATION_FOLDER}/registry-pass.txt
      
  docker run --entrypoint htpasswd registry:2.7.0 \
      -Bbn ${REGISTRY_USER} ${REGISTRY_PASS} \
      > ${DESTINATION_FOLDER}/htpasswd

  helm repo add twuni https://helm.twun.io

  kubectl create ns docker-registry
  kubectl apply -f registry-pvc.yaml
  kubectl get pvc docker-registry-pv-claim -n docker-registry
  helm install docker-registry twuni/docker-registry -f registry-chart.yaml --set ingress.hosts={${REGISTRY_HOST}} --namespace docker-registry 
  export POD_NAME=$(kubectl get pods --namespace docker-registry -l "app=docker-registry,release=docker-registry" -o jsonpath="{.items[0].metadata.name}")
  echo $POD_NAME
  kubectl get pods -n docker-registry

  # Wait for the registry to be up and running
  while [[ $(kubectl get pods -n docker-registry $POD_NAME -o 'jsonpath={..status.conditions[?(@.type=="Ready")].status}') != "True" ]]; do echo "Waiting for the registry to be ready" && sleep 1; done

  kubectl annotate ingress docker-registry nginx.ingress.kubernetes.io/proxy-body-size="0" --overwrite -n docker-registry

  sleep 2

  # echo "echo ${REGISTRY_PASS} | docker login -u ${REGISTRY_USER} ${REGISTRY_HOST} --password-stdin"
  echo ${REGISTRY_PASS} | docker login -u ${REGISTRY_USER} ${REGISTRY_HOST} --password-stdin


elif [ "$1" == "regcred" ]; then
  # Code to run if the first argument is "regcred"
  NAMESPACE=$2
  if [ -z "$NAMESPACE" ]; then
    echo "Namespace argument is required for regcred"
    exit 1
  fi
  echo "Creating registry credentials in namespace $NAMESPACE..."
  kubectl create secret docker-registry regcred \
    --docker-server=${REGISTRY_HOST} \
    --docker-username=${REGISTRY_USER} \
    --docker-password=${REGISTRY_PASS} \
    -n $NAMESPACE

fi

unset REGISTRY_USER REGISTRY_PASS DESTINATION_FOLDER