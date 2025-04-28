import requests as req 

url = 'http://localhost:8000/api/bus'

# |Kx bus|sirkali|chennai|2025-04-27

post_data = {
    'from_place' : 'Sirkali',
    'to_place' : 'Chennai',
    'date' : '2025-04-27'
}
response = req.post(url,json=post_data)
print(f'response code : {response.status_code}')
print(f'text : \n{response.text}')
