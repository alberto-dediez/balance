Feature:
    Ability to serve the contents of a file of the media library

    Scenario: As a user I can see the contents of a file
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/1111/show"
        Then the response status should be 200

    Scenario: An unknown file is not served
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/9999/show"
        Then the response status should be 404

    Scenario: A file of another account is not served
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "2222" exists
        Given media "logo.png" with id "3333" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/3333/show"
        Then the response status should be 403
