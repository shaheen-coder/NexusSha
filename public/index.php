<?php
// psr 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as ApiResponse;
// Slim
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Slim\Middleware\BodyParsingMiddleware;


// twig 
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
// middleware 
use App\Middleware\AuthMiddleware;
// uuid pkg 
use Ramsey\Uuid\Uuid;

require __DIR__ . '/../vendor/autoload.php';
// middleware 
require __DIR__ . '/../middlewares/AuthMiddleware.php';

// models 
require __DIR__ . '/../db/bootstrap.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../models/Bus.php';
require __DIR__ . '/../models/Booking.php';

session_start();

$app = AppFactory::create();
// request middleware 
$app->addBodyParsingMiddleware();
// twig config 
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// view 
$app->get('/', function (Request $request, Response $response, array $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.html', []);
});



// login view 
function is_auth() {
    return isset($_SESSION['user_id']);
}

$app->get('/login',  function (Request $request, Response $response){
    $view = Twig::fromRequest($request);
    return $view->render($response,'login.html',[]);
});
$app->post('/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $email = $data['email'];
    $password = $data['password'];

    $user = User::where('email', $email)->first();

    if ($user && password_verify($password, $user->password)) {
        $_SESSION['user_id'] = $user->id;
        return $response->withHeader('Location', '/')->withStatus(302);
    } else {
        $view = \Slim\Views\Twig::fromRequest($request);
        return $view->render($response, 'login.html', [
            'error' => 'Invalid email or password'
        ]);
    }
});
$app->post('/signup', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    $user = User::create([
        'username' => $data['name'],
        'email' => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_BCRYPT),
    ]);

    $_SESSION['user_id'] = $user->id;
    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->get('/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// booking view
$app->get('/book',function (Request $request,Response $response){
    if(!is_auth()){
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $view = Twig::fromRequest($request);
    return $view->render($response,'booking.html',[]);
});
// invoice of booking 
$app->get('/{id}/invoice', function (Request $request, Response $response, array $args) {
    error_log('Invoice route triggered'); 
    $id = $args['id'];
    $data = Booking::find($id);
    if (!$data) {
        throw new HttpNotFoundException($request, "Booking ID not found.");
    }

    $data->seats = json_decode($data->seats, true);

    $view = Twig::fromRequest($request);
    return $view->render($response, 'invoice.html', ['datas' => $data]);
});



// api views 
$app->post('/api/bus', function (Request $request, Response $response) {
    $data = $request->getParsedBody();  
    //error_log("data: " . print_r($data, true), 0);
    $from = $data['from_place'] ?? null;
    $to   = $data['to_place']  ?? null;
    $date = $data['date']      ?? null;

    if (!$from || !$to || !$date) {
        $payload = json_encode([
            'status' => 'failed',
            'error'  => 'Missing required fields'
        ]);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    // 4. Query
    $result = Bus::where('from_place', $from)
                 ->where('to_place',   $to)
                 ->where('date',       $date)
                 ->get();

    if ($result->isEmpty()) {
        $payload = json_encode([
            'status' => 'failed',
            'error'  => 'There is no bus available'
        ]);
    } else {
        $payload = json_encode([
            'status' => 'success',
            'data'   => $result
        ]);
    }
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/{bus_id}/bus',function  (Request $request, Response $response, array $args){
    $busId = $args['bus_id'];
    $data = Bus::where('id',$busId)->get();
    if($data->isEmpty()){
        $payload = json_encode([
            'status' => 'failed',
            'error' => 'bus not found '
        ]);
    }else{
        $payload = json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    }
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});
// bus booking 

$app->post('/api/booking', function(Request $request, Response $response) {
    $data = $request->getParsedBody();
    $sessionUserId = $_SESSION['user_id'] ?? null;

    if (!$sessionUserId) {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode([
                'status' => 'failed',
                'error' => 'User not authenticated'
            ]));
    }

    // 2) Validate request payload
    if (
        empty($data['bus_id']) ||
        empty($data['seats']) ||
        empty($data['userd']['aid']) ||
        empty($data['userd']['address']) ||
        empty($data['userd']['phone']) ||
        empty($data['userd']['name'])
    ) {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode([
                'status' => 'failed',
                'error' => 'Missing required fields'
            ]));
    }

    $requestedSeats = $data['seats'];
    if (!is_array($requestedSeats) || count($requestedSeats) === 0) {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode([
                'status' => 'failed',
                'error' => 'Seats must be a non-empty array'
            ]));
    }

    // 3) Load the bus
    $bus = Bus::find($data['bus_id']);
    if (!$bus) {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode([
                'status' => 'failed',
                'error' => 'Bus not found'
            ]));
    }

    // 4) Decode seat map
    $seatMap = json_decode($bus->seats, true) ?? [];

    // 5) Check seat availability
    foreach ($requestedSeats as $seat) {
        if (!isset($seatMap[$seat]) || $seatMap[$seat] != 0) {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode([
                    'status' => 'failed',
                    'error' => "Seat {$seat} is not available"
                ]));
        }
    }

    // 6) Mark requested seats as booked (1)
    foreach ($requestedSeats as $seat) {
        $seatMap[$seat] = 1;
    }

    // 7) Save updated bus seat map
    $bus->seats = json_encode($seatMap);
    $bus->save();

    // 8) Create booking record
    $booking = Booking::create([
        'id'         => Uuid::uuid4()->toString(),
        'user_id'    => $sessionUserId,
        'bus_id'     => $bus->id,
        'price'      => $data['price'] ?? 0,
        'aadhar_no'  => $data['userd']['aid'],
        'address'    => $data['userd']['address'],
        'phone'      => $data['userd']['phone'],
        'name'       => $data['userd']['name'],
        'seats'      => json_encode($requestedSeats),
    ]);

    // 9) Return success response
    $payload = json_encode([
        'status'           => 'success',
        'booking_id'       => $booking->id,
        'bus_id'           => $bus->id,
        'seats_reserved'   => $requestedSeats,
        'seats_remaining'  => array_keys(array_filter($seatMap, fn($v) => $v === 0))
    ]);
    
    $response->getBody()->write($payload);
    return $response;    
});

// handle 404 

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app) {
    $response = new \Slim\Psr7\Response();
    $view = \Slim\Views\Twig::fromRequest($request);
    return $view->render($response->withStatus(404), '404.html');
});

$app->run();
