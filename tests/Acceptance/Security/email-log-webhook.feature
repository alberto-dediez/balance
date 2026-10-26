Feature:
    I want to make sure that the SendGrid webhook updates the sent emails

    Scenario: A delivered event marks the email as delivered
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"delivered","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        Then the response status should be 202
        Then email log "0199f3b2-0000-7000-8000-000000000001" has status "Delivered"

    Scenario: Two open events count two openings
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"open","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"},{"email":"first@dediez.software","timestamp":1700000100,"event":"open","sg_event_id":"e2","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        Then the response status should be 202
        Then email log "0199f3b2-0000-7000-8000-000000000001" has been opened 2 times

    Scenario: An event for an unknown email is ignored
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"delivered","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000099"}]
            """
        Then the response status should be 202
        Then email log "0199f3b2-0000-7000-8000-000000000001" has status "Sent"

    Scenario: A processed event changes nothing
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"processed","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        Then the response status should be 202
        Then email log "0199f3b2-0000-7000-8000-000000000001" has status "Sent"

    Scenario: An unknown event in the batch is ignored and the rest is applied
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"group_unsubscribe","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"},{"email":"first@dediez.software","timestamp":1700000100,"event":"delivered","sg_event_id":"e2","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        Then the response status should be 202
        Then email log "0199f3b2-0000-7000-8000-000000000001" has status "Delivered"

    Scenario: A malformed payload is rejected
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"delivered","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        Then the response status should be 406
        Then email log "0199f3b2-0000-7000-8000-000000000001" has status "Sent"
