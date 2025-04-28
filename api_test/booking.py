import requests 

url = 'http://localhost:8000/api/booking'

payload = {
    "bus_id" : 1,
    "price" : 1000,
    "seats": ["5","6"],
    "userd": {
        "name":"Shaheen Ahmed",
        "aid":"12345678",
        "address":"10 new uasa",
        "phone":"9677329608"
    }
}

response = requests.post(url,json=payload)
print(f'\t response status : {response.status_code}')
print(f'response text:\n{response.text}\n\n')