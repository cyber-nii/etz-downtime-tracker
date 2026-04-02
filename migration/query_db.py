import subprocess

def run_query(query):
    result = subprocess.run(
        [r'c:\xampp\mysql\bin\mysql.exe', '-u', 'root', 'downtimedb', '-N', '-e', query],
        capture_output=True, text=True
    )
    return result.stdout.strip()

print("=== SERVICES ===")
print(run_query("SELECT service_id, service_name FROM services"))

print("\n=== USERS ===")
print(run_query("SELECT user_id, username, full_name FROM users"))

print("\n=== COMPANIES ===")
print(run_query("SELECT company_id, company_name FROM companies"))

print("\n=== INCIDENT TYPES ===")
print(run_query("SELECT type_id, service_id, name FROM incident_types"))

print("\n=== EXISTING INCIDENT REFS ===")
print(run_query("SELECT incident_ref FROM incidents ORDER BY incident_ref"))

print("\n=== SERVICE COMPONENTS ===")
print(run_query("SELECT component_id, service_id, name FROM service_components"))
