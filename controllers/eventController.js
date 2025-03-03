// ...existing code...

// Function to handle ticket reservation
exports.reserveTicket = async (req, res) => {
    try {
        const eventId = req.params.eventId;
        const userId = req.user.id;
        const { numberOfTickets, paymentMethod } = req.body;

        // Find the event by ID
        const event = await Event.findById(eventId);
        if (!event) {
            return res.status(404).json({ message: 'Event not found' });
        }

        // Create a new reservation
        const reservation = new Reservation({
            event: eventId,
            user: userId,
            numberOfTickets: numberOfTickets,
            paymentMethod: paymentMethod
        });

        // Save the reservation
        await reservation.save();

        res.status(201).json({ message: 'Reservation successful', reservation });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error });
    }
};

// ...existing code...
