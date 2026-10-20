Feature:
    I want to make sure that old sent emails are purged

    Scenario: The purge removes the email logs older than the retention period
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "old@dediez.software" sent 200 days ago exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000002" to "recent@dediez.software" sent 10 days ago exists
        When email logs are purged
        Then email log "0199f3b2-0000-7000-8000-000000000001" does not exist
        Then email log "0199f3b2-0000-7000-8000-000000000002" has status "Sent"
