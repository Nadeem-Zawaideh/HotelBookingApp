<?php
function loadRooms() {
    $rooms = [];
    if (($handle = fopen("../data/rooms.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rooms[] = [
                "id" => $data[0],
                "name" => $data[1],
                "type" => $data[2],
                "price" => $data[3],
                "description" => $data[4]
            ];
        }
        fclose($handle);
    }
    return $rooms;
}

function saveBooking($booking) {
    $file = fopen("../data/bookings.csv", "a");
    fputcsv($file, $booking);
    fclose($file);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $roomType = trim($_POST['room_type']);
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $validRoomTypes = ["Single", "Double", "Suite"];

    if (!$name || !$roomType || !$checkIn || !$checkOut) {
        echo json_encode(["error" => "All fields are required."]);
        exit;
    }
    
    if (!in_array($roomType, $validRoomTypes)) {
        echo json_encode(["error" => "Invalid room type."]);
        exit;
    }

    if (strtotime($checkIn) >= strtotime($checkOut)) {
        echo json_encode(["error" => "Check-in date must be before check-out date."]);
        exit;
    }

    saveBooking([$name, $roomType, $checkIn, $checkOut]);
    echo json_encode(["success" => "Booking confirmed!"], JSON_PRETTY_PRINT);
    exit;
}

if (isset($_GET['room_type'])) {
    $roomType = $_GET['room_type'];
    $rooms = loadRooms();
    $filteredRooms = array_filter($rooms, function($room) use ($roomType) {
        return strtolower($room['type']) === strtolower($roomType);
    });
    echo json_encode(array_values($filteredRooms));
    exit;
}

if (isset($_GET['id'])) {
    $roomId = $_GET['id'];
    $rooms = loadRooms();
    foreach ($rooms as $room) {
        if ($room['id'] == $roomId) {
            echo json_encode($room);
            exit;
        }
    }
    echo json_encode(null);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.3.2"></script>
</head>
<body>
    <h1>Search for Available Rooms</h1>
    <form id="search-form">
        <label for="room_type">Room Type:</label>
        <select name="room_type" id="room_type">
            <option value="Single">Single</option>
            <option value="Double">Double</option>
            <option value="Suite">Suite</option>
        </select>
        <button type="submit">Search</button>
    </form>

    <div id="results"></div>
    <div id="room-details" style="display:none;"></div>
    <div id="booking-form" style="display:none;"></div>
    <div id="booking-summary" style="display:none;"></div>
    
    <script>
        document.getElementById('search-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const roomType = document.getElementById('room_type').value;
            fetch(`index.php?room_type=${roomType}`)
                .then(response => response.json())
                .then(data => {
                    let output = '<table><tr><th>Name</th><th>Type</th><th>Price</th></tr>';
                    data.forEach(room => {
                        output += `<tr><td><a href='#' onclick='viewRoom(${room.id})'>${room.name}</a></td><td>${room.type}</td><td>$${room.price}</td></tr>`;
                    });
                    output += '</table>';
                    document.getElementById('results').innerHTML = output;
                });
        });

        function viewRoom(id) {
            fetch(`index.php?id=${id}`)
                .then(response => response.json())
                .then(room => {
                    if (room) {
                        document.getElementById('results').style.display = 'none';
                        let details = `<h2>${room.name}</h2>
                                       <p><strong>Type:</strong> ${room.type}</p>
                                       <p><strong>Price:</strong> $${room.price}</p>
                                       <p>${room.description}</p>
                                       <button onclick='showBookingForm("${room.type}")'>Book Now</button>
                                       <button onclick='goBack()'>Back</button>`;
                        document.getElementById('room-details').innerHTML = details;
                        document.getElementById('room-details').style.display = 'block';
                    }
                });
        }

        function showBookingForm(roomType) {
            document.getElementById('room-details').style.display = 'none';
            document.getElementById('booking-form').innerHTML = `
                <h2>Book a Room</h2>
                <form id='book-room'>
                    <label>Name: <input type='text' name='name' required></label><br>
                    <input type='hidden' name='room_type' value='${roomType}'>
                    <label>Check-in: <input type='date' name='check_in' required></label><br>
                    <label>Check-out: <input type='date' name='check_out' required></label><br>
                    <button type='submit'>Confirm Booking</button>
                    <button type='button' onclick='goBack()'>Cancel</button>
                </form>`;
            document.getElementById('booking-form').style.display = 'block';

            document.getElementById('book-room').addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('booking-summary').innerHTML = `<h2>Booking Confirmation</h2><p>${data.success}</p>`;
                        document.getElementById('booking-summary').style.display = 'block';
                        triggerConfetti();
                    } else {
                        alert(data.error);
                    }
                });

                function triggerConfetti() {
                    confetti({
                        particleCount: 150,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
            });
        }

        function goBack() {
            document.getElementById('room-details').style.display = 'none';
            document.getElementById('booking-form').style.display = 'none';
            document.getElementById('results').style.display = 'block';
        }
    </script>

    
</body>
</html>
