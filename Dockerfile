FROM bitnami/laravel:latest

# Install cron
USER bitnami
RUN rm  composer.lock
RUN sudo apt-get update && sudo apt-get install -y cron

COPY --chown=bitnami:bitnami . /app
COPY --chown=bitnami:bitnami  run.sh /app/run.sh
COPY --chown=bitnami:bitnami  crontab /etc/cron.d/cool-task
RUN chmod 0644 /etc/cron.d/cool-task
RUN sudo chown bitnami:bitnami /var/log /etc/environment /var/run

RUN crontab /etc/cron.d/cool-task
RUN touch /var/log/cron.log
RUN echo 'test' > /var/log/cron.log

RUN sudo service cron restart

# RUN sudo cron -f
# CMD ["sudo","cron","-f"]

EXPOSE 80
