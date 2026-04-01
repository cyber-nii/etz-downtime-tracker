import pandas as pd

with open('data_inspection.txt', 'w', encoding='utf-8') as f:
    try:
        csv_df = pd.read_csv('downtimedb.csv', nrows=3)
        f.write("=== CSV Columns ===\n")
        for c in csv_df.columns:
            f.write(repr(c) + '\n')
            
        excel_df = pd.read_excel('2026-03-26T14_08_18.3898181Z.xlsx', nrows=3)
        f.write("\n=== Excel Columns ===\n")
        for c in excel_df.columns:
            f.write(repr(c) + '\n')
            
        f.write("\n=== First row Excel ===\n")
        f.write(str(excel_df.iloc[0].to_dict()) + '\n')
        
        f.write("\n=== First row CSV ===\n")
        f.write(str(csv_df.iloc[0].to_dict()) + '\n')
    except Exception as e:
        f.write("Error: " + str(e) + '\n')
