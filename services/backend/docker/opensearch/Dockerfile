FROM opensearchproject/opensearch:2

# Polish has no built-in OpenSearch language analyzer (unlike English/German/French/...),
# so the polish_stop/polish_stem filters used by PolishSearchLanguage need this plugin.
RUN /usr/share/opensearch/bin/opensearch-plugin install --batch analysis-stempel
