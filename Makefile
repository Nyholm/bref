.EXPORT_ALL_VARIABLES:

# Build the PHP runtimes
runtimes:
	cd runtime && make publish

docker-images:
	cd runtime && make docker-images
	for image in \
	  "nyholm/php-72" "nyholm/php-72-fpm" "nyholm/php-72-fpm-dev" \
	  "nyholm/php-73" "nyholm/php-73-fpm" "nyholm/php-73-fpm-dev" \
	  "nyholm/php-74" "nyholm/php-74-fpm" "nyholm/php-74-fpm-dev" \
	  "nyholm/build-php-72" \
	  "nyholm/build-php-73" \
	  "nyholm/build-php-74" \
	  "nyholm/fpm-dev-gateway"; \
	  do \
      docker tag $$image:latest $$image:${TRAVIS_TAG} ; \
      docker push $$image ; \
  done

# Generate and deploy the production version of the website using http://couscous.io
website:
	# See http://couscous.io/
	couscous generate
	netlify deploy --prod --dir=.couscous/generated
website-staging:
	couscous generate
	netlify deploy --dir=.couscous/generated

# Run a local preview of the website using http://couscous.io
website-preview:
	couscous preview

website-assets: website/template/output.css
website/template/output.css: website/node_modules website/template/styles.css website/tailwind.config.js
	cd website && npx tailwind build template/styles.css -o template/output.css
website/node_modules: website/package.json website/package-lock.json
	cd website && npm install

# Deploy the demo functions
demo:
	serverless deploy

layers.json:
	php runtime/layers/layer-list.php

.PHONY: runtimes website website-preview website-assets demo layers.json
