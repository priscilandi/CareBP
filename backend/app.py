from flask import Flask
from routes.bp_routes import bp_routes

app = Flask(__name__)

app.register_blueprint(bp_routes, url_prefix="/api")


@app.route("/health")
def health():
    return {"status": "ok"}


if __name__ == "__main__":
    app.run(debug=True)