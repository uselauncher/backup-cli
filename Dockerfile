FROM uselauncher/php-fpm-80

USER root

RUN set -ex \
  && cd /app \
  && git clone https://github.com/uselauncher/backup-cli \
  && chown -R launcher:launcher backup-cli

USER launcher

RUN set -ex \
  && cd /app/backup-cli \
  && composer install

WORKDIR /app/backup-cli

ENTRYPOINT ["php", "-d", "memory_limit=4G", "application"]