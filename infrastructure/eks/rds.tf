resource "aws_db_subnet_group" "this" {
  name       = "${var.cluster_name}-postgres"
  subnet_ids = module.vpc.private_subnets
}

resource "aws_security_group" "rds" {
  name        = "${var.cluster_name}-rds"
  description = "Allow Postgres access from EKS nodes only"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description     = "Postgres from EKS nodes"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [module.eks.node_security_group_id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_db_instance" "this" {
  identifier     = "${var.cluster_name}-postgres"
  engine         = "postgres"
  engine_version = "17"
  instance_class = var.rds_instance_class

  allocated_storage = 20
  storage_type      = "gp3"

  db_name  = "backend"
  username = "backend"
  # AWS generates and stores the password in Secrets Manager — no plaintext password
  # anywhere in state/vars. Fetch it with:
  #   aws secretsmanager get-secret-value --secret-id <rds_master_user_secret_arn output>
  manage_master_user_password = true

  db_subnet_group_name   = aws_db_subnet_group.this.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false
  multi_az               = false

  backup_retention_period = 1
  skip_final_snapshot     = true # educational cluster — no need to retain a snapshot on destroy
  deletion_protection     = false
}
