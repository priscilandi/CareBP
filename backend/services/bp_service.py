# Mock Data for Blood Pressure Categories 
# Replace with actual database queries aligned with UI choices

bp_categories = [
    {
        "category_id": 1,
        "category_name": "Normal",
        "display_order": 1
    },
    {
        "category_id": 2,
        "category_name": "Elevated",
        "display_order": 2
    },
    {
        "category_id": 3,
        "category_name": "High Blood Pressure",
        "display_order": 3
    }
]


def get_bp_categories():
    return bp_categories