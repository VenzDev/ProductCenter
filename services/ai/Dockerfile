FROM python:3.14-slim AS base

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

EXPOSE 8000

FROM base AS dev

COPY requirements-dev.txt .
RUN pip install --no-cache-dir -r requirements-dev.txt

COPY app ./app
COPY tests ./tests
COPY pytest.ini .

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]

FROM base AS prod

COPY app ./app

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
