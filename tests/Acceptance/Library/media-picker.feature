Feature:
    Ability to select an existing file or upload a new one from any other form

    Scenario: As a user I can search the library by ajax
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/search?query=logo"
        Then the response status should be 200
        Then I see a JSON that should be similar to:
        """
        {
          "elements": [],
          "totalResults": 1
        }
        """
        Then I see a text "logo.png"

    Scenario: As a user I can restrict the search to some media types
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/search?types=Document"
        Then the response status should be 200
        Then I see a text "contract.pdf"

    Scenario: As a user I can upload a new file by ajax from a form holding a picker
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I upload the file "picture.png" through the media picker of "/es/profile"
        Then the response status should be 200
        Then I see a text "picture.png"
        Then the library contains 1 files

    Scenario: An upload of a non allowed file type is rejected
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I upload the file "document.pdf" through the media picker of "/es/profile"
        Then the response status should be 400
        Then the library contains 0 files

    Scenario: An upload with a forged token is rejected
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/profile"
        When I upload the file "picture.png" through the media picker with the token "a-forged-token"
        Then the response status should be 400
        Then I see a JSON that should be similar to:
        """
        {
          "error": ""
        }
        """
        Then the library contains 0 files
