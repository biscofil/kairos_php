kubectl create namespace cert-manager

kubectl apply -f https://github.com/jetstack/cert-manager/releases/download/v1.12.6/cert-manager.crds.yaml --validate=false --namespace cert-manager

kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.12.6/cert-manager.yaml #--namespace cert-manager

kubectl get pods --namespace cert-manager

kubectl apply -f clusterissuer.yaml

# kubectl delete -f https://github.com/cert-manager/cert-manager/releases/download/v1.12.6/cert-manager.yaml --namespace cert-manager
# kubectl delete namespace cert-manager