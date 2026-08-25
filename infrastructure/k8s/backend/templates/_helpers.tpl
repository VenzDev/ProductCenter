{{/*
Image + env shared by every container that runs the backend image (the
Deployment and the migrate Job) — kept in one place so they can't drift.
*/}}
{{- define "backend.imageEnv" -}}
image: {{ .Values.image }}
env:
  {{- toYaml .Values.env | nindent 2 }}
{{- end -}}
