FROM opensearchproject/opensearch:2

# Polish has no built-in OpenSearch language analyzer (unlike English/German/French/...),
# so the polish_stop/polish_stem filters used by PolishSearchLanguage need this plugin.
RUN /usr/share/opensearch/bin/opensearch-plugin install --batch analysis-stempel

# docker-compose bind-mounts this directory instead (see docker-compose.yaml) so synonym
# edits show up without a rebuild; baked in here too since k8s has no host filesystem to
# bind-mount from — the bind mount just shadows this copy when running locally.
COPY analysis/ /usr/share/opensearch/config/analysis/
