import json

with open("measured_heights.json") as f:
    heights = json.load(f)

from get_exact_row_heights import logical_tables, row_manifest

print(f"Total manifest entries: {len(row_manifest)}, heights: {len(heights)}")

table_data = []
idx = 0
for ti, lt in enumerate(logical_tables):
    t_info = {
        "name": lt["name"],
        "header_heights": [],
        "data_heights": []
    }
    for hi in range(6):
        t_info["header_heights"].append(heights[idx])
        idx += 1
    for di in range(len(lt["data_rows"])):
        t_info["data_heights"].append(heights[idx])
        idx += 1
    t_info["total_header_height"] = sum(t_info["header_heights"])
    table_data.append(t_info)

with open("table_measurements.json", "w", encoding="utf-8") as f:
    json.dump(table_data, f, ensure_ascii=False, indent=2)

for td in table_data:
    print(f"{td['name']}: header_h={td['total_header_height']:.1f}, data_rows={len(td['data_heights'])}, sum_data_h={sum(td['data_heights']):.1f}")
