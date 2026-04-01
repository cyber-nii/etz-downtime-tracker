import json
from datetime import datetime

with open('excel_data.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

headers = data['headers']
rows = data['rows']

# Column indices
COL = {h: i for i, h in enumerate(headers)}
# Actions_Taken=0, Affected_Orgs=1, Category=2, Date_Detected=3, Description=4,
# Detector=5, Downtime_Minutes=6, End_Date_Time=7, Incident_Status=8,
# Incident_Type=9, Lessons_Learned=10, Location=11, Phone=12, Ref=13,
# Root_Cause=14, Sart_Date_Time=15, Source=16, Systems_Affected=17

# === MAPPING TABLES ===

# User mapping: Detector name -> user_id
user_map = {
    'Harry Opata': 2,
    'Maxwell Eshun': 3,
    'Eric Fillipe Arthur': 4,
    'Fredrick Hanson': 5,
    'Mary Asante': 6,
    'Takyi Owusu Mensah': 7,
    'Simon Owusu Ansah': 8,
    'Jacob Quarshie Nii Odoi': 19,
    'Anku Bright': 18,        # Map to admin (no specific user)
    'Israel Opata': 2,         # Map to Harry Opata (similar name, likely typo or relative)
}

# Company mapping: Affected_Orgs -> company_id
company_map = {
    'Abii National': 1,
    'AirtelTigo': 2,
    'All': 3,
    'All ': 3,
    'Atwima': 4,
    'BOA': 5,
    'Bestpoint': 6,
    'ECG': 7,
    'GCB': 8,
    'MTN': 9,
    'Multi Choice': 10,
    'NIB': 11,
    'NLA': 12,
    'PBL': 13,
    'SISL': 14,
    'STCCU': 15,
    'Telecel': 16,
    'VisionFund': 17,
    'eTranzact': 18,
}

# Incident Type mapping -> offset for names (logic will handle service_id lookup)
# Based on db_ref.txt:
# Connectivity Issue: 1306 (+6 for each service)
# Server Failure / Shut Down: 1307
# Service / Application not responding: 1308
# Insufficient Account Balance: 1309
# System Maintenance: 1310
# Others: 1311
incident_type_base_map = {
    'Connectivity Issue': 0,
    'Server Failure / Shut Down': 1,
    'Service / Application not responding': 2,
    'Insufficient Account Balance': 3,
    'System Maintenance': 4,
    'Others': 5,
}

service_base_id = {
    141: 1306,
    142: 1312,
    143: 1318,
    144: 1324,
    145: 1330,
    146: 1336
}

# Status mapping
status_map = {
    'Resolved': 'resolved',
    'Pending': 'pending',
}

# Downtime category mapping based on Incident_Type
downtime_category_map = {
    'Connectivity Issue': 'Network',
    'Server Failure / Shut Down': 'Server',
    'Service / Application not responding': 'Server',
    'System Maintenance': 'Maintenance',
    'Insufficient Account Balance': 'Other',
    'Others': 'Other',
}

# Component mapping Matrix from downtimedb.csv (Line 1521+)
component_matrix = {
    141: {'Credit': 130, 'Debit': 131, 'Reversal': 132, 'B2W': 133, 'W2B': 134, 'Topup': 135},
    142: {'Bills': 136, 'Topup': 137, 'Airtime': 138, 'Data Bundle': 139, 'B2W': 140, 'W2B': 141},
    143: {'Credit': 142, 'Debit': 143, 'B2W': 144, 'W2B': 145, 'Topup': 146, 'Reversal': 147},
    144: {'Credit': 148, 'Debit': 149, 'Topup': 150, 'B2W': 151, 'W2B': 152, 'Reversal': 153},
    145: {'Credit': 154, 'Debit': 155, 'Bills': 156, 'Topup': 157, 'Airtime': 158, 'Reversal': 159},
    146: {'Credit': 160, 'Debit': 161, 'B2W': 162, 'W2B': 163, 'Topup': 164, 'Reversal': 165},
}

# Synonyms for mapping
component_synonyms = {
    'top up': 'Topup',
    'bill': 'Bills',
    'data': 'Data Bundle',
    'adsl': 'Data Bundle',
}

def sql_escape(val):
    if val is None:
        return 'NULL'
    s = str(val).strip()
    if not s:
        return 'NULL'
    s = s.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n").replace("\r", "")
    return f"'{s}'"

def format_datetime(val):
    """Convert ISO datetime string to MySQL datetime format"""
    if val is None:
        return 'NULL'
    s = str(val).strip()
    if not s:
        return 'NULL'
    # Handle ISO format: 2026-03-20T19:13:00.000Z
    try:
        dt = datetime.fromisoformat(s.replace('Z', '+00:00'))
        return f"'{dt.strftime('%Y-%m-%d %H:%M:%S')}'"
    except:
        return sql_escape(s)

def is_planned(incident_type):
    return 1 if incident_type == 'System Maintenance' else 0

def map_service_id(systems_affected, description):
    """Map Systems_Affected text to the correct service_id."""
    systems = str(systems_affected or '').lower().strip()
    desc = str(description or '').lower().strip()
    
    vas_keywords = ['airtime', 'vas ', 'vasgate', 'data bundle', 'telecel airtime', 'adsl', 'voda']
    if 'vasgate' in systems or any(kw in desc for kw in vas_keywords):
        return 142
    if 'fundgate' in systems or any(kw in desc for kw in ['fundgate', 'fund gate']):
        return 143
    if 'mpay' in systems:
        return 144
    if 'justpay' in systems:
        return 145
    if 'ova' in systems:
        return 146
    if 'ussd' in systems or 'mobile money' in systems:
        return 141
        
    return 141 # Default to Mobile Money

def map_component_id(service_id, systems_affected, description):
    """Map text to the correct component_id based on service_id."""
    text = f"{systems_affected or ''} {description or ''}".lower()
    
    # Priority keywords
    keywords = ['b2w', 'w2b', 'reversal', 'top up', 'topup', 'credit', 'debit', 'bill', 'adsl', 'data', 'airtime']
    
    found_key = None
    for k in keywords:
        if k in text:
            found_key = k
            break
    
    if not found_key:
        return 'NULL'
        
    # Standardize key
    std_key = found_key.capitalize()
    if found_key in component_synonyms:
        std_key = component_synonyms[found_key]
    elif found_key == 'top up':
        std_key = 'Topup'
        
    # Look up in matrix
    service_map = component_matrix.get(service_id, component_matrix[141])
    return service_map.get(std_key, 'NULL')

# Check for missing users first
print("-- =============================================")
print("-- MIGRATION: Excel Data -> downtimedb")
print("-- Generated from 2026-03-26T14_08_18.3898181Z.xlsx")
print("-- =============================================")
print()

# Check for user 'Anku Bright' - need to add or map
missing_users = set()
for row in rows:
    detector = str(row[COL['Detector']]).strip()
    if detector not in user_map:
        missing_users.add(detector)

if missing_users:
    print(f"-- WARNING: Unmapped detectors: {missing_users}")
    print()

# Need to add 'Anku Bright' user first
print("-- =============================================")
print("-- STEP 0: Add missing users (if not already present)")
print("-- =============================================")
print("INSERT IGNORE INTO users (user_id, username, email, password_hash, full_name, role, is_active, changed_password)")
print("VALUES (25, 'anku_bright', 'anku.bright@etz.com', '$2b$10$placeholder', 'Anku Bright', 'user', 1, 0);")
print()
# Update user_map
user_map['Anku Bright'] = 25

print("INSERT IGNORE INTO users (user_id, username, email, password_hash, full_name, role, is_active, changed_password)")
print("VALUES (26, 'israel_opata', 'israel.opata@etz.com', '$2b$10$placeholder', 'Israel Opata', 'user', 1, 0);")
print()
user_map['Israel Opata'] = 26

print()
print("-- =============================================")
print("-- STEP 1: INSERT INCIDENTS")
print("-- =============================================")
print("SET FOREIGN_KEY_CHECKS = 0;")
print()

for i, row in enumerate(rows):
    ref = str(row[COL['Ref']]).strip()
    description = row[COL['Description']]
    root_cause = row[COL['Root_Cause']]
    lessons_learned = row[COL['Lessons_Learned']]
    detector = str(row[COL['Detector']]).strip()
    incident_type = str(row[COL['Incident_Type']]).strip()
    status = status_map.get(str(row[COL['Incident_Status']]).strip(), 'pending')
    start_time = row[COL['Sart_Date_Time']]
    end_time = row[COL['End_Date_Time']]
    date_detected = row[COL['Date_Detected']]
    source = str(row[COL['Source']]).strip() if row[COL['Source']] else ''
    systems_affected = row[COL['Systems_Affected']]
    actions_taken = row[COL['Actions_Taken']]
    
    user_id = user_map.get(detector, 18)  # Default to admin
    
    # Smarter Mapping
    service_id = map_service_id(systems_affected, description)
    component_id = map_component_id(service_id, systems_affected, description)
    
    # Get correct type_id based on service
    offset = incident_type_base_map.get(incident_type, 5) # Default to Others
    base = service_base_id.get(service_id, 1311)
    type_id = base + offset if base != 1311 else 1311
    
    # Determine impact_level based on downtime duration
    downtime_mins = int(row[COL['Downtime_Minutes']]) if row[COL['Downtime_Minutes']] else 0
    if downtime_mins >= 720:
        impact = 'Critical'
    elif downtime_mins >= 240:
        impact = 'High'
    elif downtime_mins >= 60:
        impact = 'Medium'
    else:
        impact = 'Low'
    
    # Priority based on impact
    priority_map = {'Critical': 'Urgent', 'High': 'High', 'Medium': 'Medium', 'Low': 'Low'}
    priority = priority_map[impact]
    
    # Build full description with systems affected
    full_desc = str(description or '').strip()
    if systems_affected:
        full_desc += f" | Systems Affected: {str(systems_affected).strip()}"
    
    resolved_by = f"{user_id}" if status == 'resolved' else 'NULL'
    resolved_at = format_datetime(end_time) if status == 'resolved' else 'NULL'
    
    created_at = format_datetime(date_detected) if date_detected else format_datetime(start_time)
    updated_at = resolved_at if status == 'resolved' else created_at
    
    print(f"-- Row {i+1}: {ref}")
    print(f"INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)")
    print(f"VALUES ({sql_escape(ref)}, {service_id}, {component_id}, {type_id}, {sql_escape(full_desc)}, '{impact}', '{priority}', {sql_escape(root_cause)}, {sql_escape(lessons_learned)}, {format_datetime(start_time)}, '{status}', {user_id}, {resolved_by}, {resolved_at}, {created_at}, {updated_at});")
    print()

print()
print("-- =============================================")
print("-- STEP 2: INSERT DOWNTIME_INCIDENTS")
print("-- (Using incident references to look up IDs)")
print("-- =============================================")
print()

for i, row in enumerate(rows):
    ref = str(row[COL['Ref']]).strip()
    start_time = row[COL['Sart_Date_Time']]
    end_time = row[COL['End_Date_Time']]
    downtime_mins = int(row[COL['Downtime_Minutes']]) if row[COL['Downtime_Minutes']] else 0
    incident_type = str(row[COL['Incident_Type']]).strip()
    
    planned = is_planned(incident_type)
    category = downtime_category_map.get(incident_type, 'Other')
    
    print(f"-- Row {i+1}: {ref}")
    print(f"INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)")
    print(f"SELECT incident_id, {format_datetime(start_time)}, {format_datetime(end_time)}, {downtime_mins}, {planned}, '{category}'")
    print(f"FROM incidents WHERE incident_ref = {sql_escape(ref)};")
    print()

print()
print("-- =============================================")
print("-- STEP 3: INSERT INCIDENT_AFFECTED_COMPANIES")
print("-- (Link incidents to affected companies)")
print("-- =============================================")
print()

for i, row in enumerate(rows):
    ref = str(row[COL['Ref']]).strip()
    affected = str(row[COL['Affected_Orgs']]).strip()
    
    # Handle multiple companies separated by ||
    orgs = [o.strip() for o in affected.split('||')]
    
    for org in orgs:
        cid = company_map.get(org)
        if cid is None:
            # Try case-insensitive match
            for k, v in company_map.items():
                if k.strip().lower() == org.lower():
                    cid = v
                    break
        
        if cid is not None:
            print(f"INSERT INTO incident_affected_companies (incident_id, company_id)")
            print(f"SELECT incident_id, {cid} FROM incidents WHERE incident_ref = {sql_escape(ref)};")
        else:
            print(f"-- WARNING: Unknown company '{org}' for incident {ref}")
    
print()
print("-- =============================================")
print("-- STEP 4: INSERT INCIDENT_UPDATES (Actions Taken)")
print("-- =============================================")
print()

for i, row in enumerate(rows):
    ref = str(row[COL['Ref']]).strip()
    actions = str(row[COL['Actions_Taken']]).strip()
    detector = str(row[COL['Detector']]).strip()
    user_id = user_map.get(detector, 18)
    
    # Split actions by || separator
    action_list = [a.strip() for a in actions.split('||') if a.strip()]
    
    for action in action_list:
        print(f"INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)")
        print(f"SELECT incident_id, {user_id}, {sql_escape(detector)}, {sql_escape(action)}")
        print(f"FROM incidents WHERE incident_ref = {sql_escape(ref)};")
    print()

print("SET FOREIGN_KEY_CHECKS = 1;")
print()
print("-- =============================================")
print("-- MIGRATION COMPLETE")
print(f"-- Total incidents: {len(rows)}")
print("-- =============================================")
