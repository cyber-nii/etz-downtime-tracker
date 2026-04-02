import json
import csv

# Load Excel data
with open('excel_data.json', 'r', encoding='utf-8') as f:
    excel = json.load(f)

headers = excel['headers']
ref_idx = headers.index('Ref')
sys_idx = headers.index('Systems_Affected')
desc_idx = headers.index('Description')

# Load CSV reference - extract the incidents section to see service_id patterns
# The CSV has the incidents table starting after the header row:
# "incident_id","incident_ref","source","category","service_id","component_id",...
csv_service_map = {}
with open('downtimedb.csv', 'r', encoding='utf-8') as f:
    content = f.read()

# Find the incidents section in CSV
lines = content.split('\n')
in_incidents = False
for line in lines:
    if '"incident_id","incident_ref"' in line:
        in_incidents = True
        # Parse header to get field positions
        reader = csv.reader([line])
        inc_headers = next(reader)
        ref_pos = inc_headers.index('incident_ref')
        svc_pos = inc_headers.index('service_id')
        desc_pos = inc_headers.index('description')
        continue
    if in_incidents and line.strip():
        try:
            reader = csv.reader([line])
            fields = next(reader)
            if len(fields) > svc_pos:
                inc_ref = fields[ref_pos]
                svc_id = fields[svc_pos]
                desc = fields[desc_pos] if len(fields) > desc_pos else ''
                csv_service_map[inc_ref] = {
                    'service_id': svc_id,
                    'description': desc
                }
        except:
            if '"' in line and line.startswith('"'):
                continue
            else:
                in_incidents = False

# Print the service_id mapping from existing reference data
print("-- Reference data service_id patterns:")
svc_counts = {}
for ref, info in csv_service_map.items():
    sid = info['service_id']
    svc_counts[sid] = svc_counts.get(sid, 0) + 1
for sid, count in sorted(svc_counts.items()):
    print(f"--   service_id {sid}: {count} incidents")
print()

# Now map Systems_Affected to service_id
# Based on reference data patterns:
# 141 = Mobile Money (credit, debit, B2W, W2B, topup)
# 142 = VASGATE (airtime, bills, data bundles, VAS transactions)
# 143 = FUNDGATE (fundgate, bank transfers)
# 144 = MPAY (MPAY server, mpay)
# 145 = JUSTPAY
# 146 = OVA

def map_service_id(systems_affected, description, ref):
    """Map Systems_Affected text to the correct service_id based on reference patterns."""
    if systems_affected is None:
        systems_affected = ''
    systems = str(systems_affected).lower().strip()
    desc = str(description).lower().strip()
    
    # Direct keyword matching
    if 'vasgate' in systems:
        return 142
    if 'fundgate' in systems:
        return 143
    if 'mpay' in systems:
        return 144
    if 'justpay' in systems:
        return 145
    if 'ova' in systems:
        return 146
    
    # Pattern-based matching from description/systems_affected
    if 'ussd' in systems:
        return 141  # USSD is Mobile Money platform
    if 'mobile money' in systems:
        return 141
    
    # Check description for VASGATE-related patterns
    if any(kw in desc for kw in ['airtime', 'vas ', 'vasgate', 'data bundle', 'telecel airtime']):
        return 142
    if any(kw in desc for kw in ['fundgate', 'fund gate']):
        return 143
    
    # Default: Mobile Money
    return 141

# Print first: list all unique Systems_Affected values with their refs
print("-- Systems_Affected values from Excel:")
unique_systems = {}
for row in excel['rows']:
    sa = str(row[sys_idx]) if row[sys_idx] else 'NULL'
    if sa not in unique_systems:
        unique_systems[sa] = []
    unique_systems[sa].append(row[ref_idx])
for sa, refs in sorted(unique_systems.items()):
    mapped = map_service_id(sa, '', '')
    print(f"--   '{sa}' -> service_id={mapped} ({len(refs)} incidents: {', '.join(refs[:3])}{'...' if len(refs) > 3 else ''})")
print()

# Generate UPDATE statements
print("-- =============================================")
print("-- FIX SERVICE_ID MAPPING")
print("-- Based on Systems_Affected from Excel data")
print("-- and reference patterns from downtimedb.csv")
print("-- =============================================")
print()

updates_needed = []
for row in excel['rows']:
    ref = row[ref_idx]
    systems = row[sys_idx]
    desc = row[desc_idx]
    new_svc = map_service_id(systems, desc, ref)
    
    if new_svc != 141:  # Only need updates for non-default
        updates_needed.append((ref, new_svc, str(systems)))

if updates_needed:
    for ref, svc_id, systems in updates_needed:
        print(f"-- Systems_Affected: {systems}")
        print(f"UPDATE incidents SET service_id = {svc_id} WHERE incident_ref = '{ref}';")
        print()
    print(f"-- Total updates: {len(updates_needed)}")
else:
    print("-- No service_id updates needed - all correctly mapped to 141 (Mobile Money)")
    print("-- Check if Systems_Affected values need different mapping")
    print()
    # List all values for manual review
    for sa, refs in sorted(unique_systems.items()):
        print(f"-- '{sa}': {refs}")
