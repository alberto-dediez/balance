Feature:
    Ability to list recorded events in web

    Scenario: As admin I can access a list of all recorded events
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a table with 1 rows

    Scenario: As normal user I can't access a list of all recorded events
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 403

    Scenario: A legacy username with HTML is shown escaped in the events log
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "<b>legacy</b>" with id "aaaaaaaa-0000-0000-0000-000000000009" exists
        Given user with id "aaaaaaaa-0000-0000-0000-000000000009" has logged in
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a text "&lt;b&gt;legacy&lt;/b&gt;"
        Then I do not see a text "<b>legacy</b>"

    Scenario: A removed user is shown struck through in the events log
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user with id "ffffffff-0000-0000-0000-000000000009" has logged in
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a text "<s>ffffffff-0000-0000-0000-000000000009</s>"
