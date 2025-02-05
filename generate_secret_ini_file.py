import secrets
import string
import base64
import logging


def generate_password(size):
  # Generate a secure password
  alphabet = string.ascii_letters + string.digits # + string.punctuation
  return ''.join(secrets.choice(alphabet) for i in range(size))

def generate_base64_password(size):
  binary_string = secrets.token_bytes(size)
  return base64.b64encode(binary_string).decode()

config = {}
with open("helm_secret.ini.example", "r") as f:
  for line in f.readlines():
    try:
      if not line.strip():
        continue
      if "=" not in line:
        key=line
        value=""
      else:
        key, value = line.split('=')
      key = key.strip()
      value = value.strip()
      config[key] = value
    except ValueError:
      # syntax error
      logging.error(f"Syntax error in line: {line}")
      pass

config['JWT_SECRET'] = generate_password(64)
config['DB_PASSWORD'] = generate_password(32)
config['APP_KEY'] = 'base64:' + generate_base64_password(64)

with open("helm_secret.ini", "w") as f:
  for key, value in config.items():
    f.write(f"{key}={value}\n")
