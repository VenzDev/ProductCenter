from fastapi import FastAPI
from prometheus_fastapi_instrumentator import Instrumentator

app = FastAPI()
Instrumentator().instrument(app).expose(app)

@app.get("/health")
def health():
    return {"status": "ok"}


@app.get("/")
def hello():
    return {"message": "hello world"}


@app.get("/hello")
def hello_endpoint():
    return {"message": "hello world"}
