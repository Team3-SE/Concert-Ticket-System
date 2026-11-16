import json

with open("table_measurements.json") as f:
    tables = json.load(f)

# Layout constants in points
PAGE_HEIGHT = 841.0
TOP_MARGIN = 108.0
BOTTOM_MARGIN = 72.0
MAX_Y = PAGE_HEIGHT - BOTTOM_MARGIN # 769.0 pt

SPACER_H = 23.0
CAPTION_H = 23.4
DATA_DICT_HEADER_H = 33.0 # "Data Dictionary" title on Page 2

pages = []
current_page_num = 2
current_y = TOP_MARGIN + DATA_DICT_HEADER_H
current_page_items = [("title", "Data Dictionary", DATA_DICT_HEADER_H)]

plan = []

for ti, t in enumerate(tables):
    t_name = t["name"]
    header_h = t["total_header_height"]
    data_heights = t["data_heights"]
    num_data_rows = len(data_heights)
    
    current_data_idx = 0
    is_first_frag = True
    
    while current_data_idx < num_data_rows:
        if is_first_frag:
            # Check if this new table can fit on current page:
            # If current page already has tables:
            is_same_page = False
            if len(current_page_items) > 1 or (len(current_page_items) == 1 and current_page_items[0][0] != "title"):
                # Table is following a previous table on same page
                needed = SPACER_H + CAPTION_H + header_h + data_heights[current_data_idx]
                if current_y + needed <= MAX_Y:
                    is_same_page = True
                else:
                    is_same_page = False
            else:
                # Top of page (or right after Data Dictionary title)
                needed = CAPTION_H + header_h + data_heights[current_data_idx]
                if current_y + needed <= MAX_Y:
                    is_same_page = True
                else:
                    is_same_page = False
            
            if not is_same_page:
                # Move to next page
                pages.append((current_page_num, current_page_items, current_y))
                current_page_num += 1
                current_y = TOP_MARGIN
                current_page_items = []
                spacer = False
            else:
                spacer = (len(current_page_items) > 1 or (len(current_page_items) == 1 and current_page_items[0][0] != "title"))
            
            caption_text = t_name
        else:
            # Continuation table: ALWAYS starts on a new page!
            pages.append((current_page_num, current_page_items, current_y))
            current_page_num += 1
            current_y = TOP_MARGIN
            current_page_items = []
            spacer = False
            caption_text = f"{t_name}(ต่อ)"
            
        # Add spacer if needed
        if spacer:
            current_page_items.append(("spacer", "", SPACER_H))
            current_y += SPACER_H
            
        # Add caption
        current_page_items.append(("caption", caption_text, CAPTION_H))
        current_y += CAPTION_H
        
        # Add header
        current_page_items.append(("header", f"Header of {t_name}", header_h))
        current_y += header_h
        
        # Fill data rows
        rows_in_this_frag = []
        while current_data_idx < num_data_rows:
            rh = data_heights[current_data_idx]
            if current_y + rh <= MAX_Y:
                rows_in_this_frag.append((current_data_idx + 1, rh))
                current_y += rh
                current_data_idx += 1
            else:
                # Row cannot fit on this page!
                break
                
        current_page_items.append(("table_data", f"Rows {rows_in_this_frag[0][0]}..{rows_in_this_frag[-1][0]}", sum(r[1] for r in rows_in_this_frag)))
        plan.append({
            "table": t_name,
            "caption": caption_text,
            "page": current_page_num,
            "rows": [r[0] for r in rows_in_this_frag],
            "end_y": current_y,
            "remaining_space": MAX_Y - current_y
        })
        
        is_first_frag = False

pages.append((current_page_num, current_page_items, current_y))

print(f"Total pages planned: {current_page_num}")
print(f"Total fragments planned: {len(plan)}")
print("\n--- Plan Summary ---")
for p in plan:
    print(f"Page {p['page']:2d}: {p['caption']:35s} | rows: {len(p['rows']):2d} (data {p['rows'][0]}..{p['rows'][-1]}) | end_y={p['end_y']:.1f} | remain={p['remaining_space']:.1f}")
