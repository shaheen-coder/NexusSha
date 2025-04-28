import sqlite3
import json
from typing import Dict, Any, List, Optional

class BusClient:
    """
    A simple client for interacting with the buses SQLite table.
    """

    def __init__(self, db_path: str = '../nexussha.db'):
        self.db_path = db_path

    def _get_connection(self) -> sqlite3.Connection:
        """Establishes and returns a database connection."""
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row # Allows accessing columns by name
        return conn

    def _create_table(self):
        """Creates the 'buses' table if it doesn't exist."""
        conn = self._get_connection()
        cursor = conn.cursor()
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS buses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                from_place TEXT NOT NULL,
                to_place TEXT NOT NULL,
                seats TEXT NOT NULL, -- Stored as JSON string
                price INTEGER NOT NULL,
                date TEXT NOT NULL, -- Stored as YYYY-MM-DD
                time TEXT NOT NULL  -- Stored as HH:MM:SS
            )
        ''')
        conn.commit()
        conn.close()
        print("Checked/Created 'buses' table.")

    def insert_bus(self, name: str, from_place: str, to_place: str, seats: Dict[int, int], price: int, date: str, time: str) -> int:
        """
        Inserts a new bus record into the database.

        Args:
            name: Name of the bus.
            from_place: Starting place.
            to_place: Destination place.
            seats: Dictionary representing seat status (e.g., {1: 0, 2: 1}).
            price: Price of the ticket.
            date: Date of travel in YYYY-MM-DD format.
            time: Time of departure in HH:MM:SS format.

        Returns:
            The ID of the newly inserted record.
        """
        conn = self._get_connection()
        cursor = conn.cursor()

        # Convert seats dictionary to JSON string
        seats_json = json.dumps(seats)

        cursor.execute('''
            INSERT INTO buses (name, from_place, to_place, seats, price, date, time)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', (name, from_place, to_place, seats_json, price, date, time))

        conn.commit()
        new_id = cursor.lastrowid
        conn.close()
        print(f"Inserted bus record with ID: {new_id}")
        return new_id

    def _row_to_bus_dict(self, row: sqlite3.Row) -> Dict[str, Any]:
        """Converts a database row object into a Python dictionary."""
        bus_dict = dict(row)
        # Convert seats JSON string back to dictionary
        bus_dict['seats'] = json.loads(bus_dict['seats'])
        return bus_dict

    def get_bus_by_id(self, bus_id: int) -> Optional[Dict[str, Any]]:
        """
        Retrieves a bus record by its ID.

        Args:
            bus_id: The ID of the bus to retrieve.

        Returns:
            A dictionary representing the bus record, or None if not found.
        """
        conn = self._get_connection()
        cursor = conn.cursor()

        cursor.execute('SELECT * FROM buses WHERE id = ?', (bus_id,))
        row = cursor.fetchone()

        conn.close()

        if row:
            return self._row_to_bus_dict(row)
        else:
            return None

    def get_all_buses(self) -> List[Dict[str, Any]]:
        """
        Retrieves all bus records from the database.

        Returns:
            A list of dictionaries, each representing a bus record.
        """
        conn = self._get_connection()
        cursor = conn.cursor()

        cursor.execute('SELECT * FROM buses')
        rows = cursor.fetchall()

        conn.close()

        return [self._row_to_bus_dict(row) for row in rows]

    # --- Example Usage ---
if __name__ == '__main__':
    bus_db = BusClient()

    # Sample data for a new bus
    sample_seats = {str(i): 0 for i in range(1, 52)} # Initialize all seats as available (0)

    # Insert a new bus record
    bus_id = bus_db.insert_bus(
        name=' SRS Bus',
        from_place='chidambaram',
        to_place='chennai',
        seats=sample_seats,
        price=5000,
        date='2025-04-29',
        time='12:00:00'
    )

    # Retrieve the bus record by its ID
    retrieved_bus = bus_db.get_bus_by_id(bus_id)
    if retrieved_bus:
        print("\nRetrieved Bus:")
        print(retrieved_bus)
        # Example: Check status of seat 10
        print(f"Status of seat 10: {retrieved_bus['seats'].get('10')}")


    # Insert another bus record
    bus_db.insert_bus(
        name='KPN Bus',
        from_place='chidambaram',
        to_place='chennai',
        seats={str(i): 0 for i in range(1, 51)}, # Another bus with 40 seats
        price=6500,
        date='2025-04-29',
        time='09:00:00'
    )

    # Retrieve all bus records
    all_buses = bus_db.get_all_buses()
    print("\nAll Buses:")
    for bus in all_buses:
        print(bus)
    
    
    bus_id = bus_db.insert_bus(
        name=' SRS Bus',
        from_place='chidambaram',
        to_place='bengaluru',
        seats=sample_seats,
        price=5000,
        date='2025-04-29',
        time='12:00:00'
    )

    # Retrieve the bus record by its ID
    retrieved_bus = bus_db.get_bus_by_id(bus_id)
    if retrieved_bus:
        print("\nRetrieved Bus:")
        print(retrieved_bus)
        # Example: Check status of seat 10
        print(f"Status of seat 10: {retrieved_bus['seats'].get('10')}")


    # Insert another bus record
    bus_db.insert_bus(
        name='KPN Bus',
        from_place='chidambaram',
        to_place='bengaluru',
        seats={str(i): 0 for i in range(1, 51)}, # Another bus with 40 seats
        price=6500,
        date='2025-04-29',
        time='09:00:00'
    )

    # Retrieve all bus records
    all_buses = bus_db.get_all_buses()
    print("\nAll Buses:")
    for bus in all_buses:
        print(bus)
    
    bus_id = bus_db.insert_bus(
        name=' SRS Bus',
        from_place='mayiladuthurai',
        to_place='bengaluru',
        seats=sample_seats,
        price=5000,
        date='2025-04-29',
        time='12:00:00'
    )

    # Retrieve the bus record by its ID
    retrieved_bus = bus_db.get_bus_by_id(bus_id)
    if retrieved_bus:
        print("\nRetrieved Bus:")
        print(retrieved_bus)
        # Example: Check status of seat 10
        print(f"Status of seat 10: {retrieved_bus['seats'].get('10')}")


    # Insert another bus record
    bus_db.insert_bus(
        name='KPN Bus',
        from_place='mayiladuthurai',
        to_place='bengaluru',
        seats={str(i): 0 for i in range(1, 51)}, # Another bus with 40 seats
        price=6500,
        date='2025-04-29',
        time='09:00:00'
    )

    # Retrieve all bus records
    all_buses = bus_db.get_all_buses()
    print("\nAll Buses:")
    for bus in all_buses:
        print(bus)
    bus_id = bus_db.insert_bus(
        name=' SRS Bus',
        from_place='mayiladuthurai',
        to_place='chennai',
        seats=sample_seats,
        price=5000,
        date='2025-04-29',
        time='12:00:00'
    )

    # Retrieve the bus record by its ID
    retrieved_bus = bus_db.get_bus_by_id(bus_id)
    if retrieved_bus:
        print("\nRetrieved Bus:")
        print(retrieved_bus)
        # Example: Check status of seat 10
        print(f"Status of seat 10: {retrieved_bus['seats'].get('10')}")


    # Insert another bus record
    bus_db.insert_bus(
        name='KPN Bus',
        from_place='mayiladuthurai',
        to_place='chennai',
        seats={str(i): 0 for i in range(1, 51)}, # Another bus with 40 seats
        price=6500,
        date='2025-04-29',
        time='09:00:00'
    )

    # Retrieve all bus records
    all_buses = bus_db.get_all_buses()
    print("\nAll Buses:")
    for bus in all_buses:
        print(bus)

   