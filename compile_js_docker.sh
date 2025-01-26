# docker run -it \
#  -v ${PWD}/node_modules:/app/node_modules \
#  -v ${PWD}/packages.json:/app/packages.json \
#  -v ${PWD}/packages-lock.json:/app/packages-lock.json \
#  node:14.15.5 /bin/bash 

docker run --rm -it \
  -v ${PWD}:/app \
  -v ${PWD}/npm_logs:/root/.npm/_logs \
  node:12.22.6 \
  /bin/bash -c 'cd /app && ./compile_js.sh'