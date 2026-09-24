Feature:
    Ability to publish the scheduled announcements from the scheduler

    Scenario: The scheduler dispatches the publication of the scheduled announcements
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Vencida" with id "11111111-1111-1111-1111-111111111111" is scheduled for "2020-01-01 10:00" with notification
        Given announcement "Futura" with id "22222222-2222-2222-2222-222222222222" is scheduled for "2030-01-01 10:00"
        When command "app:schedule:run" is executed with argument "username:admin@dediez.software"
        Then Is Executed Successfully
        Then I want to see "scheduled command(s) dispatched" in command output
        Then announcement "11111111-1111-1111-1111-111111111111" is not published
        Then announcement "22222222-2222-2222-2222-222222222222" is not published

    Scenario: Publishing the scheduled announcements publishes only the due ones and queues their notification
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Vencida" with id "11111111-1111-1111-1111-111111111111" is scheduled for "2020-01-01 10:00" with notification
        Given announcement "Futura" with id "22222222-2222-2222-2222-222222222222" is scheduled for "2030-01-01 10:00"
        When the scheduled announcements are published
        Then announcement "11111111-1111-1111-1111-111111111111" is published
        Then announcement "22222222-2222-2222-2222-222222222222" is not published
        Then a deferred "NotifyAnnouncementRequest" command is queued in the low priority transport
