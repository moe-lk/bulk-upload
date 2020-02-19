FROM bitnami/laravel:6.12.0-debian-10-r20

COPY --chown=bitnami:bitnami . /app

WORKDIR /app

RUN mkdir logs tmp
RUN mkdir /app/storage/logs

EXPOSE 80
# CMD ["/usr/sbin/apache2ctl", "-DFOREGROUND"]
