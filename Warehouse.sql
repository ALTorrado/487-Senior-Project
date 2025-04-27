-- Create Company table
CREATE TABLE Company (
    Name VARCHAR(255) NOT NULL,
    Company_Email VARCHAR(255) NOT NULL UNIQUE,
    Company_Code VARCHAR(16) NOT NULL,
    Status INT DEFAULT 0,
    PRIMARY KEY (Company_Code)
);

-- Create Users table
CREATE TABLE Users (
    User_Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password TEXT NOT NULL,
    Role INT NOT NULL,
    Approval_Status INT DEFAULT 0,
    Company_Code VARCHAR(16),
    FOREIGN KEY (Company_Code) REFERENCES Company(Company_Code)
);

-- Create Category table
CREATE TABLE Category (
    Category_Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255) NOT NULL,
    Description TEXT,
    Company_Code VARCHAR(16),
    FOREIGN KEY (Company_Code) REFERENCES Company(Company_Code)
);


-- Create Product table
CREATE TABLE Product (
    Product_Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255) NOT NULL,
    Description TEXT,
    Price INT NOT NULL,
    Stock_Quantity INT NOT NULL,

    Units VARCHAR(255),
    Category_Category_Id INT,
    Company_Code VARCHAR(16),
    FOREIGN KEY (Category_Category_Id) REFERENCES Category(Category_Id),
    FOREIGN KEY (Company_Code) REFERENCES Company(Company_Code)
);

-- Create Orders table
CREATE TABLE Orders (
    Order_Id INT PRIMARY KEY AUTO_INCREMENT,
    Order_Date DATE NOT NULL,
    Status VARCHAR(255) NOT NULL,
    Users_User_Id INT,
    Total_Amount INT,
    Payment_Status VARCHAR(50),
    Delivery_Date DATE,
    FOREIGN KEY (Users_User_Id) REFERENCES Users(User_Id)
);

-- Create Order_Products table
CREATE TABLE Order_Products (
    Order_Item_Id INT PRIMARY KEY AUTO_INCREMENT,
    Quantity INT NOT NULL,
    Price_At_Time INT NOT NULL,
    Orders_Order_Id INT,
    Product_Product_Id INT,
    FOREIGN KEY (Orders_Order_Id) REFERENCES Orders(Order_Id),
    FOREIGN KEY (Product_Product_Id) REFERENCES Product(Product_Id)
);