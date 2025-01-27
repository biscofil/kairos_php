# https://www.paulsblog.dev/how-to-install-a-private-docker-container-registry-in-kubernetes/#google_vignette

helm uninstall docker-registry --namespace docker-registry

kubectl delete secret regcred -n docker-registry

echo "Do not forget to delete the PVC and the namespace"
kubectl delete pvc docker-registry-pv-claim -n docker-registry

kubectl delete ns docker-registry
