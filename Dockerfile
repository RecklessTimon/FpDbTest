FROM php:8.2-cli-alpine

RUN set -ex; \
	\
	docker-php-ext-install -j "$(nproc)" \
            mysqli \
	;


VOLUME /srv/app
WORKDIR /srv/app

CMD ["php", "test.php"]
