from flask import Blueprint, jsonify
from services.bp_service import get_bp_categories

bp_routes = Blueprint("bp_routes", __name__)


@bp_routes.route("/bp-categories", methods=["GET"])
def bp_categories():
    return jsonify(get_bp_categories())