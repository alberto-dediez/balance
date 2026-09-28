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

    Scenario: The search only offers the files reaching the audience the field requires
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "private.png" with id "1111" exists
        Given media "manual.png" with id "2222" and visibility "System" exists
        Given media "terms.png" with id "3333" and visibility "Public" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/search?visibility=System"
        Then the response status should be 200
        Then I see a text "manual.png"
        Then I see a text "terms.png"
        Then I do not see a text "private.png"

    Scenario: The search stays in my own account unless the shared files are asked for
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given media "own.png" of account "1111" exists
        Given media "manual.png" with id "3333" shared in the library of account "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/search"
        Then the response status should be 200
        Then I see a text "own.png"
        Then I do not see a text "manual.png"

    Scenario: As a user I can pick the files the other accounts shared in the library
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given media "own.png" of account "1111" exists
        Given media "foreign.png" of account "2222" exists
        Given media "manual.png" with id "3333" shared in the library of account "2222" exists
        Given media "avatar.png" with id "4444" and visibility "System" of account "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/search?includeShared=1"
        Then the response status should be 200
        Then I see a text "own.png"
        Then I see a text "manual.png"
        Then I do not see a text "foreign.png"
        Then I do not see a text "avatar.png"

    Scenario: As a user I can upload a new file by ajax from a form holding a picker
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I upload the file "picture.png" through the media picker of "/es/profile"
        Then the response status should be 200
        Then I see a text "picture.png"
        Then the library contains 1 files

    Scenario: A file uploaded from a picker is born with the visibility the form requires
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I upload the file "picture.png" through the media picker of "/es/profile"
        Then the response status should be 200
        Then the file "picture.png" has the visibility "System"

    Scenario: An upload asking for a wider visibility than the form requires is rejected
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I upload the file "picture.png" through the media picker of "/es/profile" asking for the visibility "Public"
        Then the response status should be 400
        Then the library contains 0 files

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

    Scenario: The profile picker offers the library and a drop zone
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I see a text "Biblioteca"
        Then I see a text "Arrastra aquí el archivo o pulsa para elegirlo"
        Then I do not see a text "Seleccionar de la biblioteca"
