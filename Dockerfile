FROM moelk/laravel

COPY --chown=bitnami:bitnami . /app

WORKDIR /app

RUN mkdir logs tmp
RUN mkdir /app/storage/logs

EXPOSE 80
# CMD ["/usr/sbin/apache2ctl", "-DFOREGROUND"]
