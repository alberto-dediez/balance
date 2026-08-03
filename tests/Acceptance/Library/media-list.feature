Feature:
    Ability to browse the media library in web

    Scenario: As an admin I can access the library
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As a regular user I can also access the library
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200

    Scenario: As an admin I only see the media of my own account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "1111" exists
        Given media "own.png" of account "1111" exists
        Given media "foreign.png" of account "2222" exists
        Given media "without-account.png" with id "3333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "own.png"

    Scenario: Without account I only see the media without account
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "own.png" of account "1111" exists
        Given media "without-account.png" with id "3333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "without-account.png"

    Scenario: As a user I only see the media of my own account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given media "own.png" of account "1111" exists
        Given media "foreign.png" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a text "own.png"
