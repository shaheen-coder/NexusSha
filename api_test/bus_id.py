import requests 

url = 'http://localhost:8000/api/1/bus'

response = requests.get(url)
print(f'response status : {response.status_code}')
print(f'text :\n{response.text}')