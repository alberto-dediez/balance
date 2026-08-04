Feature:
    Ability to serve the files marked as public without being logged in

    Scenario: Anyone can see the contents of a public file
        Given media "terms.png" with id "1111" and visibility "Public" exists
        When I go to "/es/library/public/media/1111"
        Then the response status should be 200

    Scenario: A file shared only with the users of the platform is not served publicly
        Given media "manual.png" with id "1111" and visibility "System" exists
        When I go to "/es/library/public/media/1111"
        Then the response status should be 404

    Scenario: A file of an account is not served publicly
        Given account "Minotour" with id "1111" exists
        Given media "logo.png" with id "2222" of account "1111" exists
        When I go to "/es/library/public/media/2222"
        Then the response status should be 404

    Scenario: An unknown file is not served publicly
        When I go to "/es/library/public/media/9999"
        Then the response status should be 404
